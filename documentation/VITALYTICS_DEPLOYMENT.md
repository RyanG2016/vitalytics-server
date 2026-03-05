# Vitalytics - Standalone Deployment Guide

**Domain:** your-domain.com
**API Subdomain:** your-vitalytics-server.com

This guide documents how to extract the Health Monitor (now **Vitalytics**) from the My App Laravel application and deploy it as a standalone service on AWS Lightsail.

---

## Quick Reference

| Component | URL |
|-----------|-----|
| **API Endpoint** | `https://your-vitalytics-server.com/v1/health/events` |
| **Admin Dashboard** | `https://your-domain.com/admin/analytics` |
| **Status Check** | `https://your-vitalytics-server.com/v1/health/status/{appIdentifier}` |

---

## Architecture Decisions

### Multi-Tenant Identification (Payload-Based)

Products and platforms are identified via **HTTP headers and payload**, NOT subdomains:

```
Headers:
  X-API-Key: <unique-per-app-key>
  X-App-Identifier: myapp-ios | torque-android | another-app-web

Body:
  appIdentifier: "myapp-ios"
  environment: "production" | "staging" | "development"
```

**Why payload-based (not subdomains)?**
- Simpler SSL management (one certificate)
- Simpler DNS (no wildcard records)
- Better for SDKs (single endpoint URL)
- Lower cost (single Lightsail instance)
- All products are internal (not white-labeled for external customers)

### Product Hierarchy

```
Product (Parent)
└── Sub-Product / Platform (Child)
    └── Environment (production/staging/development)

Example:
My App
├── myapp-portal (Web Portal)
├── myapp-ios (iOS App)
├── myapp-android (Android App)
├── myapp-chrome (Chrome Extension)
└── myapp-windows (Windows Recorder)

Another App
├── torque-portal (Web Portal)
├── torque-ios (iOS App)
└── torque-android (Android App)

Another App
├── another-app-web (Web App)
└── another-app-ios (iOS App)
```

### Dashboard Features
- View **all products** at once (overview)
- Filter by **single product** (e.g., just My App)
- Drill down to **specific platform** (e.g., My App iOS)
- Filter by **environment** (production vs staging)

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [AWS Lightsail Setup](#aws-lightsail-setup)
4. [Server Configuration](#server-configuration)
5. [Laravel Project Setup](#laravel-project-setup)
6. [Code Migration](#code-migration)
7. [Database Setup](#database-setup)
8. [Environment Configuration](#environment-configuration)
9. [SSL Certificate Setup](#ssl-certificate-setup)
10. [Queue Worker Setup](#queue-worker-setup)
11. [Multi-Product Configuration](#multi-product-configuration)
12. [Testing](#testing)
13. [Maintenance](#maintenance)

---

## Overview

**Vitalytics** is a standalone service for collecting crash reports, errors, warnings, and health events from multiple client applications (iOS, Android, Web, Chrome Extension) across different products (My App, Another App, Another App, etc.).

### Features

- **Event Collection**: Receives health events via REST API
- **Real-time Dashboard**: Admin UI for monitoring all products
- **Alerting**: Slack/webhook notifications for crashes and errors
- **Geolocation**: City-level IP geolocation for events
- **Retention Management**: Automatic cleanup of old events
- **Multi-Product Support**: Monitor multiple products from one dashboard

### Estimated Costs

| Component | Monthly Cost |
|-----------|--------------|
| AWS Lightsail (2GB) | $10 USD |
| SSL (Let's Encrypt) | Free |
| **Total** | **$10 USD (~$14 CAD)** |

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Client Applications                          │
│  ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌───────────┐    │
│  │    iOS    │  │  Android  │  │    Web    │  │  Chrome   │    │
│  │   Apps    │  │   Apps    │  │   Apps    │  │ Extension │    │
│  └─────┬─────┘  └─────┬─────┘  └─────┬─────┘  └─────┬─────┘    │
└────────┼──────────────┼──────────────┼──────────────┼──────────┘
         │              │              │              │
         └──────────────┴──────┬───────┴──────────────┘
                               │ HTTPS POST
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│                    AWS Lightsail (ca-central-1)                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                    Ubuntu 24.04 LTS                        │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐│  │
│  │  │   Apache    │  │    PHP 8.1  │  │   MariaDB 10.6      ││  │
│  │  │  + SSL      │  │  + Laravel  │  │   (vitalytics)      ││  │
│  │  └──────┬──────┘  └──────┬──────┘  └──────────┬──────────┘│  │
│  │         │                │                     │           │  │
│  │         └────────────────┴─────────────────────┘           │  │
│  │                          │                                  │  │
│  │  ┌───────────────────────┴───────────────────────────────┐ │  │
│  │  │                 Laravel Vitalytics                     │ │  │
│  │  │  ┌──────────┐  ┌──────────┐  ┌──────────────────────┐ │ │  │
│  │  │  │   API    │  │  Queue   │  │   Admin Dashboard    │ │ │  │
│  │  │  │ Endpoint │  │  Worker  │  │   (Web UI)           │ │ │  │
│  │  │  └──────────┘  └──────────┘  └──────────────────────┘ │ │  │
│  │  └───────────────────────────────────────────────────────┘ │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

---

## AWS Lightsail Setup

### Step 1: Create Lightsail Instance

1. **Log in to AWS Console** and navigate to Lightsail:
   ```
   https://lightsail.aws.amazon.com
   ```

2. **Click "Create instance"**

3. **Configure the instance:**
   - **Region**: Canada (Central) - `ca-central-1`
   - **Availability Zone**: `ca-central-1a` (or any)
   - **Platform**: Linux/Unix
   - **Blueprint**: OS Only → Ubuntu 24.04 LTS
   - **Instance plan**: $10 USD/month (2 GB RAM, 2 vCPUs, 60 GB SSD)
   - **Instance name**: `vitalytics`

4. **Click "Create instance"**

5. **Wait for instance to start** (1-2 minutes)

### Step 2: Create Static IP

1. Go to **Networking** tab in Lightsail
2. Click **Create static IP**
3. Attach to `vitalytics` instance
4. Name it: `vitalytics-ip`
5. **Note the IP address** (e.g., `52.60.xxx.xxx`)

### Step 3: Configure Firewall

1. Click on your `vitalytics` instance
2. Go to **Networking** tab
3. Under **IPv4 Firewall**, ensure these rules exist:
   ```
   Application   Protocol   Port     Source
   SSH           TCP        22       Any
   HTTP          TCP        80       Any
   HTTPS         TCP        443      Any
   ```

### Step 4: Configure DNS

Add A records in your DNS provider:
```
your-domain.com      →  52.60.xxx.xxx (your static IP)
your-vitalytics-server.com  →  52.60.xxx.xxx (same static IP)
```

Both domains point to the same server. Apache virtual hosts will route traffic appropriately.

---

## Server Configuration

### Step 1: Connect via SSH

**Option A: Browser-based SSH**
- Click instance → Connect → "Connect using SSH"

**Option B: Terminal SSH**
- Download SSH key from Lightsail (Account → SSH Keys)
- Connect:
  ```bash
  chmod 400 ~/Downloads/LightsailDefaultKey-ca-central-1.pem
  ssh -i ~/Downloads/LightsailDefaultKey-ca-central-1.pem ubuntu@52.60.xxx.xxx
  ```

### Step 2: Update System

```bash
sudo apt update && sudo apt upgrade -y
```

### Step 3: Install Required Packages

```bash
# Install Apache, PHP, MariaDB
sudo apt install -y \
    apache2 \
    mariadb-server \
    php8.3 \
    php8.3-mysql \
    php8.3-xml \
    php8.3-curl \
    php8.3-mbstring \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-gd \
    libapache2-mod-php8.3 \
    unzip \
    git \
    curl

# Enable Apache modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers

# Install Composer
cd ~
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js (for asset building)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### Step 4: Configure MariaDB

```bash
# Secure MariaDB installation
sudo mysql_secure_installation
```

Follow the prompts:
- Enter current password for root: (just press Enter)
- Switch to unix_socket authentication: N
- Set root password: Y (set a strong password)
- Remove anonymous users: Y
- Disallow root login remotely: Y
- Remove test database: Y
- Reload privilege tables: Y

### Step 5: Create Database and User

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE vitalytics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'vitalytics_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON vitalytics.* TO 'vitalytics_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 6: Configure Apache Virtual Hosts

Create two virtual hosts - one for the dashboard, one for the API subdomain:

```bash
sudo nano /etc/apache2/sites-available/vitalytics.conf
```

Add the following content:

```apache
# Main dashboard site
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/vitalytics/public

    <Directory /var/www/vitalytics/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/vitalytics-error.log
    CustomLog ${APACHE_LOG_DIR}/vitalytics-access.log combined
</VirtualHost>

# API subdomain (same Laravel app, different domain)
<VirtualHost *:80>
    ServerName your-vitalytics-server.com
    DocumentRoot /var/www/vitalytics/public

    <Directory /var/www/vitalytics/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Optional: Set environment variable for API-specific logic
    SetEnv VITALYTICS_API_SUBDOMAIN true

    ErrorLog ${APACHE_LOG_DIR}/vitalytics-api-error.log
    CustomLog ${APACHE_LOG_DIR}/vitalytics-api-access.log combined
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite vitalytics.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

---

## Laravel Project Setup

Choose ONE of the following options:

### Option A: Clone from Git Repository (Recommended for New Deployments)

If you have the Vitalytics repo ready, clone it directly:

```bash
cd /var/www
sudo mkdir vitalytics
sudo chown ubuntu:ubuntu vitalytics

# Clone the repository
git clone git@github.com:your-org/vitalytics.git /var/www/vitalytics

# Install dependencies
cd /var/www/vitalytics
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Copy environment file and configure
cp .env.example .env
nano .env  # Edit database credentials, API keys, etc.

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate
```

### Option B: Convert Existing Laravel Installation to Git Repo

If you already ran `composer create-project` and want to switch to git:

```bash
cd /var/www/vitalytics

# Backup your .env file (it's gitignored and won't be overwritten)
cp .env .env.backup

# Initialize git and connect to remote
git init
git remote add origin git@github.com:your-org/vitalytics.git

# Fetch and reset to match remote (overwrites Laravel default files)
git fetch origin
git reset --hard origin/main

# Restore your .env if needed
cp .env.backup .env

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Run any new migrations
php artisan migrate
```

### Option C: Fresh Laravel Install (Only if No Repo Exists Yet)

Use this only if you're setting up before the repo is created:

```bash
cd /var/www
sudo mkdir vitalytics
sudo chown ubuntu:ubuntu vitalytics
cd vitalytics

# Create new Laravel project
composer create-project laravel/laravel:^10.0 .
```

Then later, when the repo is ready, use Option B to convert.

### Set Permissions (All Options)

```bash
sudo chown -R www-data:www-data /var/www/vitalytics
sudo chmod -R 755 /var/www/vitalytics
sudo chmod -R 775 /var/www/vitalytics/storage
sudo chmod -R 775 /var/www/vitalytics/bootstrap/cache
```

---

## Code Migration

Copy the following files from the My App project to the new Vitalytics project:

### Directory Structure

```
vitalytics/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── HealthMonitorSetup.php
│   │       └── HealthMonitorCleanup.php
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Api/
│   │       │   └── HealthEventsController.php
│   │       └── Admin/
│   │           └── AnalyticsDashboardController.php
│   ├── Jobs/
│   │   ├── ProcessHealthEvents.php
│   │   └── CheckHealthAlerts.php
│   └── Models/
│       └── HealthEvent.php
├── config/
│   └── health-monitor.php
├── database/
│   └── migrations/
│       └── 2024_01_15_000000_create_health_tables.php
├── resources/
│   └── views/
│       └── admin/
│           └── analytics/
│               ├── index.blade.php
│               ├── events.blade.php
│               ├── devices.blade.php
│               └── show.blade.php
└── routes/
    └── health-monitor.php
```

### Files to Copy

#### 1. Models

**Source:** `app/Models/HealthEvent.php`

Copy to: `app/Models/HealthEvent.php`

**Modification needed:** Change the connection from `health_monitor` to `mysql` (since this will be the only database):

```php
// Change this line:
protected $connection = 'health_monitor';

// To:
protected $connection = 'mysql';
```

#### 2. Controllers

**Source:** `app/Http/Controllers/Api/HealthEventsController.php`

Copy to: `app/Http/Controllers/Api/HealthEventsController.php`

**Source:** `app/Http/Controllers/Admin/AnalyticsDashboardController.php`

Copy to: `app/Http/Controllers/Admin/AnalyticsDashboardController.php`

**Modification needed:** Update database connection references:

```php
// Change all occurrences of:
DB::connection('health_monitor')

// To:
DB::connection('mysql')  // or just DB:: (uses default)
```

#### 3. Jobs

**Source:** `app/Jobs/ProcessHealthEvents.php`

Copy to: `app/Jobs/ProcessHealthEvents.php`

**Modification needed:** Update database connection:

```php
// Change:
DB::connection('health_monitor')->table('health_stats')

// To:
DB::table('health_stats')
```

**Source:** `app/Jobs/CheckHealthAlerts.php`

Copy to: `app/Jobs/CheckHealthAlerts.php`

**Modification needed:** Update database connection:

```php
// Change:
DB::connection('health_monitor')->table('health_alerts')

// To:
DB::table('health_alerts')
```

#### 4. Console Commands

**Source:** `app/Console/Commands/HealthMonitorSetup.php`

Copy to: `app/Console/Commands/HealthMonitorSetup.php`

**Source:** `app/Console/Commands/HealthMonitorCleanup.php`

Copy to: `app/Console/Commands/HealthMonitorCleanup.php`

**Modification needed:** Update database connection references from `health_monitor` to default.

#### 5. Configuration

**Source:** `config/health-monitor.php`

Copy to: `config/health-monitor.php`

#### 6. Migration

**Source:** `database/migrations/2024_01_15_000000_create_health_tables.php`

Copy to: `database/migrations/2024_01_15_000000_create_health_tables.php`

**Modification needed:** Remove the connection specification:

```php
// Remove this line:
protected $connection = 'health_monitor';

// Change all occurrences of:
Schema::connection('health_monitor')->create(...)

// To:
Schema::create(...)
```

#### 7. Views

Copy entire directory:
```
resources/views/admin/analytics/
├── index.blade.php
├── events.blade.php
├── devices.blade.php
└── show.blade.php
```

**Modification needed:** Update views to use standalone layout (see Step 3 below).

#### 8. Routes

Create `routes/api.php` with health routes:

```php
<?php

use App\Http\Controllers\Api\HealthEventsController;
use Illuminate\Support\Facades\Route;

// Health Monitoring API
Route::prefix('v1/health')->group(function () {
    Route::post('/events', [HealthEventsController::class, 'store']);
    Route::get('/status/{appIdentifier}', [HealthEventsController::class, 'status']);
    Route::get('/events/{appIdentifier}', [HealthEventsController::class, 'index']);
    Route::get('/events/{appIdentifier}/{eventId}', [HealthEventsController::class, 'show']);
});
```

Create `routes/web.php` with admin routes:

```php
<?php

use App\Http\Controllers\Admin\AnalyticsDashboardController;
use Illuminate\Support\Facades\Route;

// Public home page (optional)
Route::get('/', function () {
    return redirect()->route('admin.analytics');
});

// Admin Dashboard (add authentication as needed)
Route::prefix('admin')->group(function () {
    Route::get('/analytics', [AnalyticsDashboardController::class, 'index'])
        ->name('admin.analytics');
    Route::get('/analytics/events', [AnalyticsDashboardController::class, 'events'])
        ->name('admin.analytics.events');
    Route::get('/analytics/events/{id}', [AnalyticsDashboardController::class, 'show'])
        ->name('admin.analytics.show');
});
```

### Step 3: Create Standalone Layout

Create a new layout file `resources/views/layouts/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Health Monitor</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gray-100 dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('admin.analytics') }}" class="flex items-center">
                        <i class="fas fa-heartbeat text-red-500 text-2xl mr-2"></i>
                        <span class="font-bold text-xl text-gray-900 dark:text-white">Health Monitor</span>
                    </a>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ now()->format('M d, Y g:i A') }}
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>
</body>
</html>
```

Update views to use `<x-app-layout>` component wrapper.

### Step 4: Update Kernel.php for Scheduled Tasks

Edit `app/Console/Kernel.php`:

```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Clean up old health events daily at 3 AM
        $schedule->command('health-monitor:cleanup --no-interaction')
            ->dailyAt('03:00')
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
```

---

## Database Setup

### Run Migrations

```bash
cd /var/www/health-monitor
php artisan migrate
```

This creates the following tables:
- `health_events` - Main events table
- `health_stats` - Aggregated statistics
- `health_alerts` - Alert configurations

---

## Environment Configuration

Edit `/var/www/vitalytics/.env`:

```env
APP_NAME="Vitalytics"
APP_ENV=production
APP_KEY=base64:GENERATE_NEW_KEY
APP_DEBUG=false
APP_URL=https://your-domain.com

# API subdomain (used for CORS, documentation, etc.)
API_URL=https://your-vitalytics-server.com

LOG_CHANNEL=daily
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vitalytics
DB_USERNAME=vitalytics_user
DB_PASSWORD=YOUR_SECURE_PASSWORD

CACHE_DRIVER=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

# =============================================================================
# Health Monitor API Keys
# =============================================================================
# Generate unique 32-character keys for each sub-product:
# php artisan tinker --execute="echo Str::random(32);"
#
# Each key is tied to a specific sub-product identifier.
# SDKs send this key in the X-API-Key header.
# =============================================================================

# --- My App Product ---
HEALTH_MONITOR_KEY_MYAPP_PORTAL=your_32_char_random_key_here
HEALTH_MONITOR_KEY_MYAPP_IOS=your_32_char_random_key_here
HEALTH_MONITOR_KEY_MYAPP_ANDROID=your_32_char_random_key_here
HEALTH_MONITOR_KEY_MYAPP_CHROME=your_32_char_random_key_here
HEALTH_MONITOR_KEY_MYAPP_WINDOWS=your_32_char_random_key_here
HEALTH_MONITOR_KEY_MYAPP_MACOS=your_32_char_random_key_here

# --- Another App Product ---
HEALTH_MONITOR_KEY_TORQUE_PORTAL=your_32_char_random_key_here
HEALTH_MONITOR_KEY_TORQUE_IOS=your_32_char_random_key_here
HEALTH_MONITOR_KEY_TORQUE_ANDROID=your_32_char_random_key_here

# --- Another App Product ---
HEALTH_MONITOR_KEY_ANOTHERAPP_WEB=your_32_char_random_key_here
HEALTH_MONITOR_KEY_ANOTHERAPP_IOS=your_32_char_random_key_here

# =============================================================================
# Dashboard Access
# =============================================================================
# Read-only key for external dashboard access (if needed)
HEALTH_MONITOR_READ_KEY=your_read_only_key_here

# =============================================================================
# Alerting
# =============================================================================
# Slack webhook for crash/error notifications
HEALTH_MONITOR_SLACK_WEBHOOK=https://hooks.slack.com/services/xxx/yyy/zzz

# =============================================================================
# Data Retention
# =============================================================================
# Days to keep events before automatic cleanup (default: 90)
HEALTH_MONITOR_RETENTION_DAYS=90

# Max unique devices per hour before anomaly alert (default: 1000)
HEALTH_MONITOR_ANOMALY_THRESHOLD=1000
```

Generate application key:

```bash
php artisan key:generate
```

Cache configuration:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## SSL Certificate Setup

### Install Certbot and Get Certificates

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Get SSL certificates for BOTH domains
sudo certbot --apache -d your-domain.com -d your-vitalytics-server.com
```

Follow the prompts:
- Enter email address
- Agree to terms of service
- Choose whether to redirect HTTP to HTTPS (recommended: Yes)

This creates a single certificate covering both domains.

### Verify Auto-Renewal

```bash
sudo certbot renew --dry-run
```

Certbot automatically sets up a systemd timer for renewal.

### Verify SSL Configuration

After Certbot runs, verify both domains work:

```bash
curl -I https://your-domain.com
curl -I https://your-vitalytics-server.com
```

---

## Queue Worker Setup

### Create Systemd Service

```bash
sudo nano /etc/systemd/system/vitalytics-queue.service
```

Add the following content:

```ini
[Unit]
Description=Vitalytics Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/vitalytics
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable vitalytics-queue
sudo systemctl start vitalytics-queue
```

Check status:

```bash
sudo systemctl status vitalytics-queue
```

### Create Scheduler Cron Job

```bash
sudo crontab -e
```

Add this line:

```cron
* * * * * cd /var/www/vitalytics && php artisan schedule:run >> /dev/null 2>&1
```

---

## Multi-Product Configuration

### Update config/health-monitor.php

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Products Configuration
    |--------------------------------------------------------------------------
    |
    | Hierarchical product structure:
    | Product (parent) → Sub-Products/Platforms (children)
    |
    | Each sub-product has a unique identifier used in X-App-Identifier header.
    | Format: {product-slug}-{platform}
    |
    */
    'products' => [
        'myapp' => [
            'name' => 'My App',
            'description' => 'Veterinary AI dictation and transcription',
            'icon' => 'fa-microphone-alt',
            'color' => '#4F46E5', // Indigo
            'sub_products' => [
                'myapp-portal' => [
                    'name' => 'Web Portal',
                    'platform' => 'web',
                    'icon' => 'fa-globe',
                    'color' => 'blue',
                ],
                'myapp-ios' => [
                    'name' => 'iOS App',
                    'platform' => 'ios',
                    'icon' => 'fa-apple',
                    'color' => 'gray',
                ],
                'myapp-android' => [
                    'name' => 'Android App',
                    'platform' => 'android',
                    'icon' => 'fa-android',
                    'color' => 'green',
                ],
                'myapp-chrome' => [
                    'name' => 'Chrome Extension',
                    'platform' => 'chrome',
                    'icon' => 'fa-chrome',
                    'color' => 'yellow',
                ],
                'myapp-windows' => [
                    'name' => 'Windows Recorder',
                    'platform' => 'windows',
                    'icon' => 'fa-windows',
                    'color' => 'cyan',
                ],
                'myapp-macos' => [
                    'name' => 'macOS Recorder',
                    'platform' => 'macos',
                    'icon' => 'fa-apple',
                    'color' => 'gray',
                ],
            ],
        ],
        'torque' => [
            'name' => 'Another App',
            'description' => 'Automotive shop scheduling and management',
            'icon' => 'fa-wrench',
            'color' => '#DC2626', // Red
            'sub_products' => [
                'torque-portal' => [
                    'name' => 'Web Portal',
                    'platform' => 'web',
                    'icon' => 'fa-globe',
                    'color' => 'blue',
                ],
                'torque-ios' => [
                    'name' => 'iOS App',
                    'platform' => 'ios',
                    'icon' => 'fa-apple',
                    'color' => 'gray',
                ],
                'torque-android' => [
                    'name' => 'Android App',
                    'platform' => 'android',
                    'icon' => 'fa-android',
                    'color' => 'green',
                ],
            ],
        ],
        'another-app' => [
            'name' => 'Another App',
            'description' => 'AI-powered memory preservation',
            'icon' => 'fa-comments',
            'color' => '#059669', // Emerald
            'sub_products' => [
                'another-app-web' => [
                    'name' => 'Web App',
                    'platform' => 'web',
                    'icon' => 'fa-globe',
                    'color' => 'blue',
                ],
                'another-app-ios' => [
                    'name' => 'iOS App',
                    'platform' => 'ios',
                    'icon' => 'fa-apple',
                    'color' => 'gray',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Keys (Write - for SDKs sending events)
    |--------------------------------------------------------------------------
    |
    | Each sub-product gets a unique API key.
    | Generate keys with: php artisan tinker --execute="echo Str::random(32);"
    |
    */
    'api_keys' => [
        // My App
        'myapp-portal' => env('HEALTH_MONITOR_KEY_MYAPP_PORTAL'),
        'myapp-ios' => env('HEALTH_MONITOR_KEY_MYAPP_IOS'),
        'myapp-android' => env('HEALTH_MONITOR_KEY_MYAPP_ANDROID'),
        'myapp-chrome' => env('HEALTH_MONITOR_KEY_MYAPP_CHROME'),
        'myapp-windows' => env('HEALTH_MONITOR_KEY_MYAPP_WINDOWS'),
        'myapp-macos' => env('HEALTH_MONITOR_KEY_MYAPP_MACOS'),

        // Another App
        'torque-portal' => env('HEALTH_MONITOR_KEY_TORQUE_PORTAL'),
        'torque-ios' => env('HEALTH_MONITOR_KEY_TORQUE_IOS'),
        'torque-android' => env('HEALTH_MONITOR_KEY_TORQUE_ANDROID'),

        // Another App
        'another-app-web' => env('HEALTH_MONITOR_KEY_ANOTHERAPP_WEB'),
        'another-app-ios' => env('HEALTH_MONITOR_KEY_ANOTHERAPP_IOS'),
    ],

    // ... rest of config remains the same
];
```

### Dashboard Views

The dashboard supports three levels of filtering:

1. **All Products View** (`/admin/analytics`)
   - Overview cards for each product
   - Aggregate crash/error counts
   - Health score per product

2. **Single Product View** (`/admin/analytics/product/{slug}`)
   - All sub-products for that product
   - Product-specific metrics
   - Example: `/admin/analytics/product/myapp`

3. **Sub-Product View** (`/admin/analytics/app/{identifier}`)
   - Detailed events for specific platform
   - Device breakdown, version distribution
   - Example: `/admin/analytics/app/myapp-ios`

### Helper Functions

```php
// Get parent product from sub-product identifier
function getProductFromIdentifier(string $identifier): ?string
{
    $products = config('health-monitor.products');
    foreach ($products as $slug => $product) {
        if (isset($product['sub_products'][$identifier])) {
            return $slug;
        }
    }
    return null;
}

// Get all sub-product identifiers for a product
function getSubProductIdentifiers(string $productSlug): array
{
    $product = config("health-monitor.products.{$productSlug}");
    return $product ? array_keys($product['sub_products']) : [];
}
```

---

## Testing

### Test API Endpoint

```bash
# Test from your local machine using the API subdomain
curl -X POST https://your-vitalytics-server.com/v1/health/events \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your_api_key_here" \
  -H "X-App-Identifier: myapp-ios" \
  -d '{
    "batchId": "test-batch-001",
    "deviceInfo": {
      "deviceId": "test-device-001",
      "deviceModel": "iPhone 15 Pro",
      "osVersion": "iOS 17.2",
      "appVersion": "2.5.0",
      "buildNumber": "125",
      "platform": "ios"
    },
    "appIdentifier": "myapp-ios",
    "environment": "production",
    "events": [
      {
        "id": "evt-test-001",
        "timestamp": "2025-01-07T12:00:00Z",
        "level": "info",
        "message": "Test event from deployment verification"
      }
    ],
    "sentAt": "2025-01-07T12:00:00Z"
  }'
```

Expected response:

```json
{
  "success": true,
  "batchId": "test-batch-001",
  "eventsReceived": 1
}
```

### Test Different Event Levels

```bash
# Test crash event (triggers alerting)
curl -X POST https://your-vitalytics-server.com/v1/health/events \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your_api_key_here" \
  -H "X-App-Identifier: myapp-ios" \
  -d '{
    "batchId": "test-batch-002",
    "deviceInfo": {
      "deviceId": "test-device-001",
      "deviceModel": "iPhone 15 Pro",
      "osVersion": "iOS 17.2",
      "appVersion": "2.5.0",
      "platform": "ios"
    },
    "appIdentifier": "myapp-ios",
    "environment": "production",
    "events": [
      {
        "id": "evt-test-002",
        "timestamp": "2025-01-07T12:00:00Z",
        "level": "crash",
        "message": "EXC_BAD_ACCESS: Test crash event",
        "stackTrace": ["Frame 1", "Frame 2", "Frame 3"]
      }
    ],
    "sentAt": "2025-01-07T12:00:00Z"
  }'
```

### Test Status Endpoint

```bash
# Get health status for a sub-product
curl https://your-vitalytics-server.com/v1/health/status/myapp-ios \
  -H "X-API-Key: your_read_key_here" \
  -H "X-App-Identifier: dashboard"
```

### Test Dashboard

Open in browser: `https://your-domain.com/admin/analytics`

### Verify Product Hierarchy in Dashboard

1. **All Products**: `https://your-domain.com/admin/analytics`
2. **My App Only**: `https://your-domain.com/admin/analytics/product/myapp`
3. **My App iOS**: `https://your-domain.com/admin/analytics/app/myapp-ios`

---

## Maintenance

### Daily Tasks (Automated)

- **Event Cleanup**: Runs at 3:00 AM via scheduler
- **SSL Renewal**: Certbot auto-renews certificates

### Manual Maintenance

**View Logs:**
```bash
# Application logs
tail -f /var/www/vitalytics/storage/logs/laravel-$(date +%Y-%m-%d).log

# Apache logs
tail -f /var/log/apache2/vitalytics-error.log
```

**Restart Queue Worker:**
```bash
sudo systemctl restart vitalytics-queue
```

**Clear Caches:**
```bash
cd /var/www/vitalytics
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**Update Application:**
```bash
cd /var/www/vitalytics
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart vitalytics-queue
```

### Backup

**Database Backup:**
```bash
mysqldump -u vitalytics_user -p vitalytics > /home/ubuntu/backups/vitalytics_$(date +%Y%m%d).sql
```

**Create Lightsail Snapshot:**
1. Go to Lightsail Console
2. Click on instance
3. Go to "Snapshots" tab
4. Click "Create snapshot"

---

## Security Considerations

1. **API Keys**: Use strong, unique 32-character keys for each app
2. **Dashboard Access**: Consider adding basic auth or IP whitelisting
3. **Firewall**: Only ports 22, 80, and 443 should be open
4. **Updates**: Regularly update Ubuntu packages: `sudo apt update && sudo apt upgrade`
5. **Monitoring**: Set up AWS CloudWatch alerts for instance health

---

## Troubleshooting

### Common Issues

**1. 500 Error on API:**
```bash
# Check Laravel logs
tail -f /var/www/vitalytics/storage/logs/laravel.log

# Check permissions
sudo chown -R www-data:www-data /var/www/vitalytics/storage
```

**2. Queue Jobs Not Processing:**
```bash
# Check queue worker status
sudo systemctl status vitalytics-queue

# Restart worker
sudo systemctl restart vitalytics-queue
```

**3. SSL Certificate Issues:**
```bash
# Check certificate status
sudo certbot certificates

# Force renewal
sudo certbot renew --force-renewal
```

**4. Database Connection Failed:**
```bash
# Test connection
mysql -u vitalytics_user -p vitalytics -e "SELECT 1"

# Check MariaDB status
sudo systemctl status mariadb
```

---

## Files Reference

### Files to Copy from My App

| Source File | Destination | Modifications Required |
|-------------|-------------|----------------------|
| `app/Models/HealthEvent.php` | Same | Change connection to `mysql` |
| `app/Http/Controllers/Api/HealthEventsController.php` | Same | Remove connection prefix |
| `app/Http/Controllers/Admin/AnalyticsDashboardController.php` | Same | Remove connection prefix |
| `app/Jobs/ProcessHealthEvents.php` | Same | Remove connection prefix |
| `app/Jobs/CheckHealthAlerts.php` | Same | Remove connection prefix |
| `app/Console/Commands/HealthMonitorSetup.php` | Same | Remove connection prefix |
| `app/Console/Commands/HealthMonitorCleanup.php` | Same | Remove connection prefix |
| `config/health-monitor.php` | Same | Add multi-product config |
| `database/migrations/2024_01_15_000000_create_health_tables.php` | Same | Remove connection prefix |
| `resources/views/admin/analytics/*.blade.php` | Same | Update layout reference |

---

## Next Steps After Deployment

1. **Update SDK configurations** in iOS, Android, and Chrome Extension apps to point to `https://your-vitalytics-server.com`
2. **Test each app** to verify events are being received
3. **Configure Slack webhook** for crash notifications
4. **Set up Lightsail alerts** for instance monitoring
5. **Create initial snapshot** as a restore point
6. **Set up a Git repository** for the Vitalytics project for version control

---

## Handoff Checklist

Use this checklist when handing off Vitalytics deployment to another developer.

### Pre-Deployment (Server Setup)

- [ ] AWS Lightsail instance created (`ca-central-1`, 2GB plan)
- [ ] Static IP attached and noted: `_______________`
- [ ] SSH key downloaded and secured
- [ ] DNS A records configured:
  - [ ] `your-domain.com` → static IP
  - [ ] `your-vitalytics-server.com` → static IP
- [ ] DNS propagation verified (`dig your-domain.com`)

### Server Configuration

- [ ] Ubuntu packages updated (`apt update && apt upgrade`)
- [ ] Apache, PHP 8.3, MariaDB installed
- [ ] Apache modules enabled (rewrite, ssl, headers)
- [ ] Composer installed globally
- [ ] Node.js 20.x installed
- [ ] MariaDB secured (`mysql_secure_installation`)
- [ ] Database and user created

### Laravel Deployment

- [ ] Git repository cloned (or converted from fresh Laravel)
- [ ] `.env` file configured with:
  - [ ] `APP_KEY` generated
  - [ ] Database credentials
  - [ ] All `HEALTH_MONITOR_KEY_*` values generated
  - [ ] Slack webhook (optional)
- [ ] Dependencies installed (`composer install`, `npm ci`)
- [ ] Assets built (`npm run build`)
- [ ] Migrations run (`php artisan migrate`)
- [ ] Permissions set (www-data ownership, 775 on storage)

### SSL & Security

- [ ] Certbot installed
- [ ] SSL certificates obtained for both domains
- [ ] HTTP → HTTPS redirect enabled
- [ ] Firewall rules verified (22, 80, 443 only)

### Services

- [ ] Queue worker systemd service created and enabled
- [ ] Cron job added for Laravel scheduler
- [ ] Both services verified running

### Testing

- [ ] API endpoint responds: `curl https://your-vitalytics-server.com/v1/health/status/test`
- [ ] Dashboard loads: `https://your-domain.com/admin/analytics`
- [ ] Test event submitted successfully
- [ ] Test event appears in dashboard

### Documentation to Provide

- [ ] This deployment guide
- [ ] Static IP address
- [ ] SSH key location
- [ ] Database credentials (secure transfer)
- [ ] Generated API keys for each sub-product (secure transfer)
- [ ] Git repository URL

### SDK Integration Info for Developers

```
API Endpoint: https://your-vitalytics-server.com/v1/health/events
Method: POST
Headers:
  Content-Type: application/json
  X-API-Key: <provided-per-subproduct>
  X-App-Identifier: <subproduct-id>

Sub-Product Identifiers:
  My App:    myapp-portal, myapp-ios, myapp-android,
                 myapp-chrome, myapp-windows, myapp-macos
  Another App: torque-portal, torque-ios, torque-android
  Another App:  another-app-web, another-app-ios
```
