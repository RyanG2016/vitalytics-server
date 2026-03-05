# Vitalytics

A self-hosted health monitoring and analytics dashboard for your applications. Track crashes, errors, and user behavior across iOS, Android, Web, Windows, macOS, and Chrome extensions from a single dashboard.

> **Note:** I've been using Vitalytics with my own apps in production for over 6 months and recently made it public. It may need some tweaking for general public use. If something doesn't work, please [create an issue](https://github.com/RyanG2016/vitalytics-server/issues) and I'll address it.

## Features

- **Health Monitoring**: Crash reporting, error tracking, warnings, and heartbeat monitoring
- **Analytics Tracking**: User journeys, screen views, feature usage, and engagement metrics
- **Multi-Platform**: Support for iOS, Android, Web, Windows, macOS, and Chrome extensions
- **Multi-Product**: Monitor multiple apps from a single dashboard
- **Privacy-First**: Optional anonymous mode with no persistent identifiers
- **AI Summaries**: Daily summary emails powered by Claude AI (optional)
- **Real-time Alerts**: Configurable alerts with Slack/email notifications
- **Remote Configuration**: Serve config files to clients with version tracking
- **Device Registration**: Secure device provisioning with short-lived tokens
- **Maintenance Notifications**: Schedule maintenance banners for client apps

## Requirements

- PHP 8.2+
- MySQL 8.0+ or MariaDB 10.5+
- Composer
- Node.js 18+ (for asset compilation)

## Quick Start

```bash
# Clone the repository
git clone https://github.com/yourorg/vitalytics-server.git
cd vitalytics-server

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env, then run migrations
php artisan migrate

# Create an admin user
php artisan make:admin
```

## Configuration

### Environment Variables

Key configuration options in `.env`:

```bash
# Application
APP_URL=https://your-vitalytics-server.com

# Database
DB_CONNECTION=mysql
DB_DATABASE=vitalytics
DB_USERNAME=vitalytics_user
DB_PASSWORD=your_secure_password

# Vitalytics Settings
VITALYTICS_RETENTION_DAYS=90
VITALYTICS_ALERTS_ENABLED=true
VITALYTICS_SLACK_WEBHOOK=https://hooks.slack.com/...

# API Keys (one per app/platform)
VITALYTICS_KEY_MYAPP_IOS=your-secure-api-key
VITALYTICS_KEY_MYAPP_ANDROID=your-secure-api-key
```

### Adding Products

Edit `config/vitalytics.php` to add your products:

```php
'products' => [
    'myapp' => [
        'name' => 'My App',
        'description' => 'My awesome application',
        'icon' => 'fa-mobile-alt',
        'color' => '#4F46E5',
        'sub_products' => [
            'myapp-ios' => [
                'name' => 'My App iOS',
                'platform' => 'ios',
                'api_key' => env('VITALYTICS_KEY_MYAPP_IOS'),
            ],
            'myapp-android' => [
                'name' => 'My App Android',
                'platform' => 'android',
                'api_key' => env('VITALYTICS_KEY_MYAPP_ANDROID'),
            ],
        ],
    ],
],
```

## API Overview

### Health Events

```bash
POST /api/v1/health/events
X-API-Key: your-api-key
X-App-Identifier: myapp-ios

{
  "batchId": "uuid",
  "deviceInfo": {
    "deviceId": "device-uuid",
    "deviceModel": "iPhone 15",
    "osVersion": "iOS 17.0",
    "appVersion": "1.0.0",
    "platform": "ios"
  },
  "appIdentifier": "myapp-ios",
  "environment": "production",
  "events": [{
    "id": "event-uuid",
    "timestamp": "2026-01-15T10:30:00Z",
    "level": "error",
    "message": "Failed to load data"
  }]
}
```

### Analytics Events

```bash
POST /api/v1/analytics/events
X-API-Key: your-api-key
X-App-Identifier: myapp-ios

{
  "deviceInfo": { ... },
  "events": [{
    "id": "event-uuid",
    "timestamp": "2026-01-15T10:30:00Z",
    "eventType": "screen_viewed",
    "sessionId": "session-uuid",
    "screen": "HomeScreen"
  }]
}
```

See `documentation/API_SPECIFICATION.md` for full API documentation.

## Production Setup

### Scheduled Tasks

Add to crontab:

```bash
* * * * * cd /path/to/vitalytics && php artisan schedule:run >> /dev/null 2>&1
```

### Queue Worker

Set up a systemd service or use Supervisor:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

See `documentation/VITALYTICS_DEPLOYMENT.md` for detailed deployment instructions.

## SDKs

Official SDKs are available in separate repositories:

- **[Laravel SDK](https://github.com/RyanG2016/vitalytics-laravel-sdk)**: Health monitoring and analytics for Laravel applications
- **[.NET SDK](https://github.com/RyanG2016/vitalytics-dotnet-sdk)**: For Windows, macOS, and cross-platform .NET applications
- **JavaScript SDK**: Included in `public/sdk/vitalytics.js` for web and Chrome extensions

## Documentation

- `documentation/API_SPECIFICATION.md` - API reference
- `documentation/SDK-SPECIFICATION.md` - SDK development guide
- `documentation/VITALYTICS_DEPLOYMENT.md` - Deployment guide
- `docs/SDK-REMOTE-CONFIG.md` - Remote configuration guide
- `docs/MOBILE-AUTH-API-SPEC.md` - Mobile authentication API

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please read our contributing guidelines before submitting pull requests.
