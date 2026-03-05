# Vitalytics SDK Specification

Master template for building Vitalytics SDKs across all platforms.

---

## Table of Contents

1. [Overview](#overview)
2. [Two Systems: Health vs Analytics](#two-systems-health-vs-analytics)
3. [Consent & Privacy](#consent--privacy)
4. [Health Monitoring (Crashlytics Replacement)](#health-monitoring-crashlytics-replacement)
5. [Analytics Tracking](#analytics-tracking)
6. [User Feedback](#user-feedback)
7. [Auto-Tracking Requirements](#auto-tracking-requirements)
8. [API Endpoints](#api-endpoints)
9. [Payload Specifications](#payload-specifications)
10. [SDK Configuration Options](#sdk-configuration-options)
11. [Platform-Specific Notes](#platform-specific-notes)

---

## Overview

Vitalytics SDKs provide two independent systems:

| System | Purpose | Consent Required |
|--------|---------|------------------|
| **Health** | Crash reporting, errors, heartbeats | No (first-party) |
| **Analytics** | User behavior, screen views, clicks | No (first-party)* |

*When using Vitalytics as first-party. Third-party SDK customers may need consent.

---

## Two Systems: Health vs Analytics

### Health Monitoring

**Purpose:** Application stability and crash reporting (Crashlytics replacement)

**What it captures:**
- Crashes (unhandled exceptions)
- Non-fatal errors
- Warnings and info events
- Heartbeats (uptime monitoring)
- Breadcrumbs (user actions before crash)
- Device state (memory, disk, CPU)
- Log buffer (recent log messages)

**Key principle:** Health data is essential for app stability and typically exempt from consent requirements.

### Analytics Tracking

**Purpose:** User behavior and product insights

**What it captures:**
- Screen/page views
- Button clicks and interactions
- Feature usage
- Custom events
- User journeys (within sessions)

**Key principle:** Analytics should auto-track by default with minimal developer code.

---

## Consent & Privacy

### First-Party vs Third-Party

| Scenario | Consent Required? | Reason |
|----------|-------------------|--------|
| **First-party** (your app → your Vitalytics) | **No** | Legitimate interest, data stays internal |
| **Third-party** (customer's app → your Vitalytics) | **Maybe** | Customer may need to disclose/consent |
| **Using with Firebase/Google Analytics** | **Yes** | Data shared with third party |

### SDK Must Support Both Scenarios

```
// First-party use (no consent needed)
CollectionMode = DataCollectionMode.Identified

// Third-party/consent-required use
ConsentMode = ConsentMode.StandardWithPrivacyFallback
```

### Consent Mode: StandardWithPrivacyFallback

**How it works:**
- User accepts → **Standard Analytics** (with device ID)
- User declines → **Privacy Mode** (no device ID, BUT STILL TRACKS)

**Key insight:** Analytics are ALWAYS collected. Consent only controls whether `deviceId` is included.

### Data Collected by Mode

| Data | Standard Analytics | Privacy Mode |
|------|-------------------|--------------|
| Screen views | Yes | Yes |
| Button clicks | Yes | Yes |
| Feature usage | Yes | Yes |
| Session ID | Yes | Yes |
| Platform/OS | Yes | Yes |
| App version | Yes | Yes |
| Geographic region | Yes | Yes |
| **Device ID** | **Yes** | **No (null)** |
| **User ID** | Optional | **No (null)** |

---

## Health Monitoring (Crashlytics Replacement)

Vitalytics Health is a **complete first-party replacement for Firebase Crashlytics**.

### Feature Parity

| Feature | Crashlytics | Vitalytics Health | Implementation |
|---------|-------------|-------------------|----------------|
| Crash reports | ✓ | ✓ | Auto-capture unhandled exceptions |
| Stack traces | ✓ | ✓ | Include with crash/error events |
| Non-fatal errors | ✓ | ✓ | `ReportError()` method |
| Breadcrumbs | ✓ | ✓ | Circular buffer, last 100 actions |
| Device state | ✓ | ✓ | Memory, disk, CPU at crash time |
| Log buffer | ✓ | ✓ | Last 50 log messages |
| Custom keys | ✓ | ✓ | Metadata dictionary |
| Heartbeats | - | ✓ | Uptime monitoring (better!) |
| First-party | ✗ | ✓ | No consent needed |

### Breadcrumbs Implementation

**Purpose:** Track sequence of user actions leading to a crash.

**Buffer:** Circular buffer, default 100 entries (configurable).

**Types:**
```
Navigation:    { category: "navigation", from: "Home", to: "Settings" }
User Action:   { category: "user_action", message: "Clicked Save" }
Network:       { category: "network", method: "GET", url: "/api/users", status: 200 }
System:        { category: "system", message: "App entered background" }
Error:         { category: "error", message: "Failed to load", level: "error" }
Custom:        { category: "custom_category", message: "...", data: {...} }
```

**SDK Methods:**
```
AddBreadcrumb(from, to)                    // Navigation
AddBreadcrumb(action)                      // User action
AddBreadcrumb(category, message, data)     // Custom
AddBreadcrumb(Breadcrumb)                  // Full object
ClearBreadcrumbs()
```

### Device State Implementation

**Capture at crash/error time:**

```json
{
  "memory_used_by_app": 125829120,
  "memory_total": 8589934592,
  "memory_usage_percent": 45.2,
  "disk_available": 10737418240,
  "disk_total": 256060514304,
  "disk_usage_percent": 95.8,
  "thread_count": 12,
  "uptime_seconds": 3600.5,
  "processor_count": 8,
  "architecture": "x64",
  "runtime_version": ".NET 8.0.1",
  "is_debug": false
}
```

### Log Buffer Implementation

**Purpose:** Include recent log messages with crash reports.

**Buffer:** Circular buffer, default 50 entries (configurable).

**Levels:** debug, info, warning, error

**SDK Methods:**
```
LogDebug(message, tag?)
LogInfo(message, tag?)
LogWarning(message, tag?)
LogError(message, tag?)
Log(level, message, tag?)
ClearLogs()
```

### Health Event Payload

```json
{
  "id": "uuid",
  "event_timestamp": "2026-01-14T12:00:00Z",
  "event_type": "crash",
  "level": "critical",
  "message": "NullReferenceException: Object reference not set",
  "stack_trace": "at MyApp.Services.UserService.GetUser()...",
  "exception_type": "System.NullReferenceException",
  "source": "MyApp.Services.UserService",
  "device_id": "device-uuid",
  "app_version": "1.2.3",
  "os_version": "Windows 11",
  "device_model": "DESKTOP-ABC123",
  "breadcrumbs": [
    { "timestamp": "...", "category": "navigation", "message": "Home → Settings" },
    { "timestamp": "...", "category": "user_action", "message": "Clicked Save" }
  ],
  "device_state": {
    "memory_used_by_app": 125829120,
    "disk_available": 10737418240,
    "thread_count": 12
  },
  "logs": [
    { "timestamp": "...", "level": "info", "message": "User logged in" },
    { "timestamp": "...", "level": "warning", "message": "Low memory" }
  ],
  "metadata": {
    "user_id": "user-123",
    "screen": "SettingsView"
  }
}
```

---

## Analytics Tracking

### Event Types

| Event Type | When Used | Required Fields |
|------------|-----------|-----------------|
| `screen_viewed` | Screen/page navigation | screen, screenLabel |
| `button_clicked` | User clicks button/element | element, elementLabel |
| `feature_used` | Feature usage tracking | feature, category |
| `custom` | Any custom event | eventType |

### Analytics Event Payload

```json
{
  "id": "uuid",
  "timestamp": "2026-01-14T12:00:00Z",
  "eventType": "screen_viewed",
  "sessionId": "session-uuid",
  "category": "navigation",
  "screen": "SettingsScreen",
  "screenLabel": "Application Settings",
  "element": null,
  "elementLabel": null,
  "properties": {
    "tab": "general"
  }
}
```

### Analytics Batch Payload

```json
{
  "events": [...],
  "deviceInfo": {
    "deviceId": "device-uuid",
    "platform": "windows",
    "osVersion": "Windows 11",
    "appVersion": "1.2.3",
    "runtimeVersion": ".NET 8.0"
  },
  "isTest": false
}
```

**Note:** `deviceId` is `null` in Privacy Mode.

---

## User Feedback

### Overview

User Feedback allows end-users to submit feedback directly from applications. This provides valuable qualitative data alongside quantitative analytics.

**Key features:**
- Bug reports from users
- Feature requests
- General feedback with ratings
- Praise/testimonials

**Consent:** User feedback is user-initiated and typically doesn't require additional consent beyond what the user explicitly provides.

### Feedback Categories

| Category | API Value | Use Case |
|----------|-----------|----------|
| General | `general` | Default category for general feedback |
| Bug | `bug` | Bug reports and issues |
| Feature Request | `feature-request` | Feature suggestions |
| Praise | `praise` | Positive feedback, testimonials |

### Feedback Payload

```json
{
  "appIdentifier": "myapp-windows",
  "message": "The dark mode toggle doesn't work on the settings page",
  "category": "bug",
  "rating": 3,
  "email": "user@example.com",
  "userId": "user-123",
  "deviceId": "device-uuid",
  "sessionId": "session-uuid",
  "screen": "SettingsScreen",
  "deviceInfo": {
    "platform": "windows",
    "osVersion": "Windows 11",
    "appVersion": "1.2.3"
  },
  "metadata": {
    "browser": "Chrome 120",
    "custom_field": "value"
  },
  "isTest": false
}
```

### Field Constraints

| Field | Required | Max Length | Description |
|-------|----------|------------|-------------|
| `appIdentifier` | Yes | 100 | Your application identifier |
| `message` | Yes | 10000 | The feedback message |
| `category` | No | - | Default: `general`. Values: `general`, `bug`, `feature-request`, `praise` |
| `rating` | No | - | Integer 1-5 (optional) |
| `email` | No | 255 | User's email for follow-up (must be valid email format) |
| `userId` | No | 255 | Custom user identifier (string) |
| `deviceId` | No | 100 | From SDK's device info |
| `sessionId` | No | 100 | Current session ID |
| `screen` | No | 100 | Current screen/page |
| `deviceInfo.platform` | No | 50 | Platform identifier |
| `deviceInfo.osVersion` | No | 255 | OS version or user agent |
| `deviceInfo.appVersion` | No | 50 | App version string |
| `metadata` | No | - | Additional custom data (object) |
| `isTest` | No | - | Boolean test flag |

### SDK Methods

**Primary Method:**
```
SubmitFeedback(message: string, options?: FeedbackOptions) → FeedbackResult
```

**Convenience Methods:**
```
SubmitBugReport(message: string, options?) → FeedbackResult
SubmitFeatureRequest(message: string, options?) → FeedbackResult
SubmitPraise(message: string, options?) → FeedbackResult
```

**Options:**
```
category: 'general' | 'bug' | 'feature-request' | 'praise'
rating: 1-5 (optional)
email: string (optional)
userId: string (optional)
screen: string (optional)
metadata: Record<string, any> (optional)
```

**Result:**
```
{
  success: boolean
  error?: string
}
```

### API Endpoint

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/v1/feedback` | POST | Submit user feedback |

**Headers:**
```
X-API-Key: your-api-key
X-App-Identifier: your-app-identifier
X-SDK-Version: dotnet-1.4.0
Content-Type: application/json
```

### Dashboard Integration

Feedback is displayed in the Vitalytics dashboard:
- **Feedback List** - View all feedback with filtering by category, rating, product
- **Unread Badge** - Shows count of unread feedback in navigation
- **Mark Read/Unread** - Manage feedback review status
- **Reply via Email** - Click email to reply to user

### Implementation Notes

1. **Rate Limiting**: SDK should limit feedback submissions (10/minute suggested)
2. **Message Length**: Maximum 10,000 characters
3. **Validation**: Validate rating is 1-5 integer if provided, email must be valid format
4. **Current Screen**: SDK should track current screen and include automatically
5. **Device Info**: Auto-populate from SDK's device information
6. **OS Version**: Browser user agents can exceed 100 chars; field supports up to 255 chars
7. **Type Coercion**: Ensure `rating` is integer (not string), `userId` is string (not integer)

---

## Auto-Tracking Requirements

### Philosophy

**Auto-tracking is the PRIMARY approach.** SDKs should automatically capture user interactions without requiring developers to add code to every element.

### What Must Be Auto-Tracked

| Interaction | Priority | How |
|-------------|----------|-----|
| **Button clicks** | Required | Class handlers / global event listeners |
| **Screen views** | Required | Navigation observers / window activation |
| **Menu clicks** | Required | Class handlers |
| **Form submissions** | High | Form submit events |
| **Toggle changes** | High | CheckBox/Switch state changes |
| **Link clicks** | Medium | Anchor element clicks |
| **Scroll depth** | Low | Scroll position tracking |

### Auto-Tracking vs Consent (IMPORTANT)

These are **INDEPENDENT** concepts:

| Concept | What It Controls | Always Active? |
|---------|------------------|----------------|
| **Auto-Tracking** | Event capture (developer convenience) | Yes |
| **Consent Mode** | Device ID in payload (user privacy) | Yes |

**Auto-tracking always captures events regardless of consent.**
**Consent only affects what identifiers are included in the payload.**

### Implementation Pattern

```
1. Initialize SDK
2. Enable auto-tracking (register global event handlers)
3. Events are captured automatically
4. Consent controls deviceId in payload (not event capture)
```

### Element Identification Priority

When auto-tracking, identify elements in this order:

1. **Explicit tracking ID** - `data-vitalytics-id` or attached property
2. **Element name/ID** - HTML `id` or control `Name`
3. **Accessibility ID** - `aria-label` or `AutomationId`
4. **Generated** - From element type and parent context

### XAML/HTML Attributes for Customization

```xml
<!-- XAML -->
<Button v:VitalyticsAttached.TrackId="save_btn"
        v:VitalyticsAttached.TrackLabel="Save Document"
        v:VitalyticsAttached.TrackCategory="toolbar" />
```

```html
<!-- HTML -->
<button data-vitalytics-id="save_btn"
        data-vitalytics-label="Save Document"
        data-vitalytics-category="toolbar">Save</button>
```

---

## API Endpoints

Base URL: `https://your-vitalytics-server.com`

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/v1/health/events` | POST | Health events batch |
| `/api/v1/health/heartbeat` | POST | Heartbeat ping |
| `/api/v1/analytics/events` | POST | Analytics events batch |
| `/api/v1/feedback` | POST | User feedback submission |

### Authentication

Header: `X-Api-Key: {appId}:{appSecret}`

Or: `Authorization: Basic {base64(appId:appSecret)}`

---

## SDK Configuration Options

### Required Options

```
AppId: string           - Application identifier
AppSecret: string       - API secret key
```

### Common Options

```
BaseUrl: string         - API base URL (default: https://your-vitalytics-server.com)
Debug: bool             - Enable debug logging (default: false)
IsTest: bool            - Mark events as test data (default: false)
AppVersion: string?     - Application version
DeviceId: string?       - Override device ID (auto-generated if not set)
```

### Health Options

```
Enabled: bool                      - Enable health monitoring (default: true)
EnableHeartbeat: bool              - Send periodic heartbeats (default: true)
HeartbeatInterval: TimeSpan        - Heartbeat frequency (default: 5 min)
CaptureUnhandledExceptions: bool   - Auto-capture crashes (default: true)
IncludeStackTrace: bool            - Include stack traces (default: true)
MaxStackTraceLength: int           - Max stack trace chars (default: 10000)
MaxBreadcrumbs: int                - Breadcrumb buffer size (default: 100)
MaxLogEntries: int                 - Log buffer size (default: 50)
CaptureDeviceState: bool           - Capture memory/disk info (default: true)
```

### Analytics Options

```
Enabled: bool                      - Enable analytics (default: true)
CollectionMode: enum               - Anonymous or Identified (default: Anonymous)
ConsentMode: enum                  - None or StandardWithPrivacyFallback (default: None)
AutoTrackRequests: bool            - Auto-track HTTP requests (default: true, web only)
PhiSafeMode: bool                  - Strip sensitive data (default: false)
SessionTimeout: TimeSpan           - Session idle timeout (default: 30 min)
```

---

## Platform-Specific Notes

### .NET / C#

**Packages:**
- `Vitalytics.SDK` - Core SDK
- `Vitalytics.SDK.AspNetCore` - ASP.NET Core integration
- `Vitalytics.SDK.Avalonia` - Avalonia UI auto-tracking
- `Vitalytics.SDK.Wpf` - WPF auto-tracking (planned)

**Unhandled Exception Capture:**
```csharp
AppDomain.CurrentDomain.UnhandledException += handler;
TaskScheduler.UnobservedTaskException += handler;  // For async
```

### JavaScript / Web

**Auto-tracking:**
- `window.onerror` for errors
- `window.onunhandledrejection` for promise rejections
- Click event delegation on `document`
- `popstate` / `pushState` for SPA navigation

**Storage:**
- `localStorage` for device ID persistence
- `chrome.storage.local` for Chrome extensions

### iOS / Swift

**Unhandled Exception Capture:**
```swift
NSSetUncaughtExceptionHandler(handler)
signal(SIGABRT, signalHandler)
signal(SIGSEGV, signalHandler)
```

### Android / Kotlin

**Unhandled Exception Capture:**
```kotlin
Thread.setDefaultUncaughtExceptionHandler(handler)
```

---

## Test Mode

### Purpose

Mark events as test data during development. Test events are:
- Stored separately from production data
- Excluded from production analytics
- Viewable via "Show Test Data" toggle in dashboard

### Implementation

```
IsTest: bool property on SDK options
- Set at initialization: IsTest = true
- Runtime toggle: Vitalytics.SetTestMode(true)

Payload includes: "isTest": true
```

### Recommended Pattern

```csharp
#if DEBUG
    IsTest = true
#else
    IsTest = false
#endif
```

---

## Checklist for New SDKs

### Health (Crashlytics Replacement)
- [ ] Unhandled exception capture
- [ ] Stack trace collection
- [ ] ReportError() for non-fatal errors
- [ ] ReportWarning() and ReportInfo()
- [ ] Heartbeat with configurable interval
- [ ] Breadcrumb buffer (circular, configurable size)
- [ ] AddBreadcrumb() methods (navigation, action, custom)
- [ ] Log buffer (circular, configurable size)
- [ ] LogDebug/Info/Warning/Error() methods
- [ ] Device state capture (memory, disk, threads)
- [ ] Include breadcrumbs + logs + state with crash events

### Analytics
- [ ] Screen view tracking
- [ ] Button/element click tracking
- [ ] Feature usage tracking
- [ ] Custom event tracking
- [ ] Session management
- [ ] Auto-tracking implementation (platform-specific)

### User Feedback
- [ ] SubmitFeedback() method
- [ ] SubmitBugReport() convenience method
- [ ] SubmitFeatureRequest() convenience method
- [ ] SubmitPraise() convenience method
- [ ] Category validation (general, bug, feature-request, praise)
- [ ] Rating validation (1-5 or null)
- [ ] Include deviceId, sessionId, screen automatically

### Common
- [ ] Device ID generation and persistence
- [ ] Consent mode support (StandardWithPrivacyFallback)
- [ ] Test mode support (IsTest flag)
- [ ] Event batching and queuing
- [ ] Offline queue for failed sends
- [ ] Flush on app close/background
- [ ] Debug logging option

---

## Version History

| Version | Changes |
|---------|---------|
| 1.4.0 | User feedback collection (bug reports, feature requests, praise) |
| 1.3.0 | Crashlytics replacement (breadcrumbs, device state, logs) |
| 1.2.x | Consent modes, runtime test mode |
| 1.1.0 | Privacy mode (anonymous collection) |
| 1.0.x | Initial release |
