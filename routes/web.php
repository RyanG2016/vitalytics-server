<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AnalyticsDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SecretController;
use App\Http\Controllers\Admin\ProductIconController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\EventLabelMappingController;
use App\Http\Controllers\Admin\VitalyticsSystemController;
use App\Http\Controllers\Admin\MonitoredDeviceController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Admin\ProductFeedbackController;
use App\Http\Controllers\Admin\AppConfigController;
use App\Http\Controllers\Admin\RegistrationTokenController;
use App\Http\Controllers\Admin\RegisteredDeviceController;
use App\Http\Controllers\Admin\MaintenanceNotificationController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\Api\MobileAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Vitalytics dashboard routes.
| Dashboard is served from your-vitalytics-server.com
|
*/

// Token-based login for mobile app WebViews
// GET /auth/token-login?token=ws_abc123...
// Exchanges a one-time token for a web session and redirects to dashboard
Route::get('/auth/token-login', [MobileAuthController::class, 'exchangeWebSessionToken'])
    ->name('auth.token-login');

// Analytics Dashboard at root (protected by auth)
Route::middleware(['auth', 'verified'])->group(function () {
    // Main dashboard - both route names point to same controller
    Route::get('/', [AnalyticsDashboardController::class, 'index'])->name('dashboard');
    Route::get('/events', [AnalyticsDashboardController::class, 'events'])->name('admin.analytics.events');
    Route::get('/devices', [AnalyticsDashboardController::class, 'devices'])->name('admin.analytics.devices');
    Route::get('/event/{id}', [AnalyticsDashboardController::class, 'show'])->name('admin.analytics.show');
    Route::delete('/events/dismiss', [AnalyticsDashboardController::class, 'dismissEvents'])->name('admin.analytics.dismiss');

    // Analytics tracking routes
    Route::get('/analytics/sessions', [AnalyticsDashboardController::class, 'sessions'])->name('admin.analytics.sessions');
    Route::get('/analytics/session/{sessionId}', [AnalyticsDashboardController::class, 'sessionExplorer'])->name('admin.analytics.session');
    Route::get('/analytics/geo-map', [AnalyticsDashboardController::class, 'geoMap'])->name('admin.analytics.geo-map');
    Route::get('/analytics/summaries', [AnalyticsDashboardController::class, 'summaries'])->name('admin.analytics.summaries');
    Route::get('/analytics/screens', [AnalyticsDashboardController::class, 'screenActivity'])->name('admin.analytics.screens');
    Route::get('/analytics/events', [AnalyticsDashboardController::class, 'analyticsEvents'])->name('admin.analytics.tracking-events');
    Route::get('/analytics/metrics', [AnalyticsDashboardController::class, 'metrics'])->name('admin.analytics.metrics');

    // Product Feedback (SDK-submitted)
    Route::get('/analytics/feedback', [ProductFeedbackController::class, 'index'])->name('admin.product-feedback.index');
    Route::post('/analytics/feedback/mark-all-read', [ProductFeedbackController::class, 'markAllRead'])->name('admin.product-feedback.mark-all-read');
    Route::post('/analytics/feedback/{feedback}/read', [ProductFeedbackController::class, 'markRead'])->name('admin.product-feedback.mark-read');
    Route::post('/analytics/feedback/{feedback}/unread', [ProductFeedbackController::class, 'markUnread'])->name('admin.product-feedback.mark-unread');
    Route::delete('/analytics/feedback/{feedback}', [ProductFeedbackController::class, 'destroy'])->name('admin.product-feedback.destroy');

    // Feedback
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
});

// Admin-only routes (user management)
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    // Vitalytics System Dashboard
    Route::get('/system', [VitalyticsSystemController::class, 'index'])->name('admin.system.index');

    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

    // Secret management
    Route::get('/secrets', [SecretController::class, 'index'])->name('admin.secrets.index');
    Route::post('/secrets/{appIdentifier}/generate', [SecretController::class, 'generate'])->name('admin.secrets.generate');
    Route::post('/secrets/{secret}/extend', [SecretController::class, 'extend'])->name('admin.secrets.extend');
    Route::post('/secrets/{secret}/revoke', [SecretController::class, 'revoke'])->name('admin.secrets.revoke');

    // Product icon management
    Route::get('/icons', [ProductIconController::class, 'index'])->name('admin.icons.index');
    Route::put('/icons/{productId}', [ProductIconController::class, 'update'])->name('admin.icons.update');
    Route::delete('/icons/{productId}', [ProductIconController::class, 'destroy'])->name('admin.icons.destroy');

    // Product and App management
    Route::get('/products', [ProductController::class, 'index'])->name('admin.products.index');
    Route::post('/products/reorder', [ProductController::class, 'updateOrder'])->name('admin.products.reorder');
    Route::get('/products/create', [ProductController::class, 'create'])->name('admin.products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('admin.products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');

    // Apps (nested under products)
    Route::get('/products/{product}/apps/create', [ProductController::class, 'createApp'])->name('admin.products.apps.create');
    Route::post('/products/{product}/apps', [ProductController::class, 'storeApp'])->name('admin.products.apps.store');
    Route::get('/products/{product}/apps/{app}/edit', [ProductController::class, 'editApp'])->name('admin.products.apps.edit');
    Route::put('/products/{product}/apps/{app}', [ProductController::class, 'updateApp'])->name('admin.products.apps.update');
    Route::delete('/products/{product}/apps/{app}', [ProductController::class, 'destroyApp'])->name('admin.products.apps.destroy');
    Route::post('/products/{product}/apps/{app}/regenerate-key', [ProductController::class, 'regenerateApiKey'])->name('admin.products.apps.regenerate-key');
    Route::get('/products/{product}/apps/{app}/show-key', [ProductController::class, 'showApiKey'])->name('admin.products.apps.show-key');
    Route::post('/products/{product}/apps/{app}/generate-secret', [ProductController::class, 'generateSecret'])->name('admin.products.apps.generate-secret');
    Route::post('/products/{product}/apps/{app}/link', [ProductController::class, 'linkApp'])->name('admin.products.apps.link');
    Route::delete('/products/{product}/apps/{app}/unlink', [ProductController::class, 'unlinkApp'])->name('admin.products.apps.unlink');

    // Event Label Mapping management
    Route::get('/label-mappings', [EventLabelMappingController::class, 'index'])->name('admin.label-mappings.index');
    Route::post('/label-mappings', [EventLabelMappingController::class, 'store'])->name('admin.label-mappings.store');
    Route::delete('/label-mappings/{id}', [EventLabelMappingController::class, 'destroy'])->name('admin.label-mappings.destroy');

    // Alert management
    Route::get('/alerts', [AlertController::class, 'index'])->name('admin.alerts.index');
    Route::get('/alerts/{product}', [AlertController::class, 'edit'])->name('admin.alerts.edit');
    Route::put('/alerts/{product}', [AlertController::class, 'update'])->name('admin.alerts.update');
    Route::post('/alerts/{product}/test-webhook', [AlertController::class, 'testWebhook'])->name('admin.alerts.test-webhook');
    Route::post('/alerts/{product}/subscribers', [AlertController::class, 'addSubscriber'])->name('admin.alerts.add-subscriber');
    Route::put('/alerts/subscribers/{subscriber}', [AlertController::class, 'updateSubscriber'])->name('admin.alerts.update-subscriber');
    Route::delete('/alerts/subscribers/{subscriber}', [AlertController::class, 'removeSubscriber'])->name('admin.alerts.remove-subscriber');
    Route::post('/alerts/history/{alert}/clear', [AlertController::class, 'clearAlert'])->name('admin.alerts.clear');
    Route::post('/alerts/{product}/clear-all', [AlertController::class, 'clearAllAlerts'])->name('admin.alerts.clear-all');

    // Device monitoring management
    Route::get('/devices', [MonitoredDeviceController::class, 'index'])->name('admin.devices.index');
    Route::patch('/devices/{device}/archive', [MonitoredDeviceController::class, 'archive'])->name('admin.devices.archive');
    Route::patch('/devices/{device}/activate', [MonitoredDeviceController::class, 'activate'])->name('admin.devices.activate');
    Route::put('/devices/{device}', [MonitoredDeviceController::class, 'update'])->name('admin.devices.update');
    Route::delete('/devices/{device}', [MonitoredDeviceController::class, 'destroy'])->name('admin.devices.destroy');
    Route::post('/devices/bulk-archive', [MonitoredDeviceController::class, 'bulkArchive'])->name('admin.devices.bulk-archive');
    Route::post('/devices/{device}/snooze', [MonitoredDeviceController::class, 'snooze'])->name('admin.devices.snooze');
    Route::delete('/devices/{device}/snooze', [MonitoredDeviceController::class, 'cancelSnooze'])->name('admin.devices.cancel-snooze');

    // Remote Configuration management
    Route::get('/configs', [AppConfigController::class, 'index'])->name('admin.configs.index');
    Route::get('/configs/create', [AppConfigController::class, 'create'])->name('admin.configs.create');
    Route::post('/configs', [AppConfigController::class, 'store'])->name('admin.configs.store');
    Route::get('/configs/{config}/edit', [AppConfigController::class, 'edit'])->name('admin.configs.edit');
    Route::put('/configs/{config}', [AppConfigController::class, 'update'])->name('admin.configs.update');
    Route::delete('/configs/{config}', [AppConfigController::class, 'destroy'])->name('admin.configs.destroy');
    Route::post('/configs/{config}/rollback', [AppConfigController::class, 'rollback'])->name('admin.configs.rollback');
    Route::get('/configs/{config}/version/{version}', [AppConfigController::class, 'viewVersion'])->name('admin.configs.view-version');

    // Registration Token management
    Route::get('/registration-tokens', [RegistrationTokenController::class, 'index'])->name('admin.registration-tokens.index');
    Route::get('/registration-tokens/create', [RegistrationTokenController::class, 'create'])->name('admin.registration-tokens.create');
    Route::post('/registration-tokens', [RegistrationTokenController::class, 'store'])->name('admin.registration-tokens.store');
    Route::get('/registration-tokens/{registrationToken}', [RegistrationTokenController::class, 'show'])->name('admin.registration-tokens.show');
    Route::delete('/registration-tokens/{registrationToken}', [RegistrationTokenController::class, 'destroy'])->name('admin.registration-tokens.destroy');

    // Registered Device management
    Route::get('/registered-devices', [RegisteredDeviceController::class, 'index'])->name('admin.registered-devices.index');
    Route::get('/registered-devices/{registeredDevice}', [RegisteredDeviceController::class, 'show'])->name('admin.registered-devices.show');
    Route::post('/registered-devices/{registeredDevice}/revoke', [RegisteredDeviceController::class, 'revoke'])->name('admin.registered-devices.revoke');
    Route::post('/registered-devices/{registeredDevice}/regenerate-key', [RegisteredDeviceController::class, 'regenerateKey'])->name('admin.registered-devices.regenerate-key');
    Route::delete('/registered-devices/{registeredDevice}', [RegisteredDeviceController::class, 'destroy'])->name('admin.registered-devices.destroy');

    // Feedback management
    Route::get('/feedback', [AdminFeedbackController::class, 'index'])->name('admin.feedback.index');
    Route::put('/feedback/{feedback}', [AdminFeedbackController::class, 'update'])->name('admin.feedback.update');
    Route::delete('/feedback/{feedback}', [AdminFeedbackController::class, 'destroy'])->name('admin.feedback.destroy');

    // Maintenance Notifications
    Route::get('/maintenance', [MaintenanceNotificationController::class, 'index'])->name('admin.maintenance.index');
    Route::get('/maintenance/create', [MaintenanceNotificationController::class, 'create'])->name('admin.maintenance.create');
    Route::post('/maintenance', [MaintenanceNotificationController::class, 'store'])->name('admin.maintenance.store');
    Route::get('/maintenance/{maintenance}/edit', [MaintenanceNotificationController::class, 'edit'])->name('admin.maintenance.edit');
    Route::put('/maintenance/{maintenance}', [MaintenanceNotificationController::class, 'update'])->name('admin.maintenance.update');
    Route::delete('/maintenance/{maintenance}', [MaintenanceNotificationController::class, 'destroy'])->name('admin.maintenance.destroy');
    Route::post('/maintenance/{maintenance}/toggle', [MaintenanceNotificationController::class, 'toggle'])->name('admin.maintenance.toggle');
});

// Profile routes from Breeze
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Help pages (accessible to all authenticated users)
Route::middleware('auth')->prefix('help')->group(function () {
    Route::get('/integrations', [HelpController::class, 'integrations'])->name('help.integrations');
    Route::get('/analytics', [HelpController::class, 'analytics'])->name('help.analytics');
});

// Public SDK documentation (no authentication required)
Route::get('/docs/sdk', [HelpController::class, 'sdk'])->name('docs.sdk');
Route::get('/docs/analytics-sdk', [HelpController::class, 'analyticsSdkDocs'])->name('docs.analytics-sdk');

// Mobile App Device Token Routes (session auth for webview)
// These endpoints accept JSON and return JSON, using session authentication
Route::middleware(['auth'])->prefix('mobile')->group(function () {
    Route::get('/device-tokens', [DeviceTokenController::class, 'index'])->name('mobile.device-tokens.index');
    Route::post('/device-tokens', [DeviceTokenController::class, 'store'])->name('mobile.device-tokens.store');
    Route::patch('/device-tokens/{id}/preferences', [DeviceTokenController::class, 'updatePreferences'])->name('mobile.device-tokens.preferences');
    Route::delete('/device-tokens/{token}', [DeviceTokenController::class, 'destroy'])->name('mobile.device-tokens.destroy');
    Route::post('/test-push', [DeviceTokenController::class, 'testPush'])->name('mobile.test-push');
});

require __DIR__.'/auth.php';
