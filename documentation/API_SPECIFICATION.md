# Vitalytics API Specification

**Version:** 1.0
**Base URL:** `https://your-vitalytics-server.com/api`
**Last Updated:** January 2026

---

## Overview

Vitalytics is a health monitoring service that collects crash reports, errors, warnings, and other health events from client applications. This document describes how to integrate your application with Vitalytics.

---

## Authentication

All API requests must include authentication headers:

| Header | Description | Required |
|--------|-------------|----------|
| `X-API-Key` | Unique API key for your sub-product | Yes |
| `X-App-Identifier` | Your sub-product identifier (e.g., `myapp-ios`) | Yes |

### Example Headers
```
X-API-Key: your-api-key-here
X-App-Identifier: myapp-ios
Content-Type: application/json
```

---

## API Keys

Each sub-product (platform) has a unique API key. Configure these in your `.env` file and `config/vitalytics.php`.

### Example Sub-Products

| Product | Sub-Product | Identifier |
|---------|-------------|------------|
| **My App** | iOS App | `myapp-ios` |
| | Android App | `myapp-android` |
| | Web App | `myapp-web` |
| **Another App** | Windows App | `another-app-windows` |
| | macOS App | `another-app-macos` |

---

## Endpoints

### 1. Submit Health Events

Submit one or more health events to Vitalytics.

**Endpoint:** `POST /v1/health/events`

#### Request Body

```json
{
  "batchId": "uuid-batch-identifier",
  "deviceInfo": {
    "deviceId": "unique-device-identifier",
    "deviceModel": "iPhone 15 Pro",
    "osVersion": "iOS 17.2",
    "appVersion": "2.5.0",
    "buildNumber": "125",
    "platform": "ios"
  },
  "appIdentifier": "myapp-ios",
  "environment": "production",
  "isTest": false,
  "events": [
    {
      "id": "evt-unique-id-001",
      "timestamp": "2026-01-07T12:00:00Z",
      "level": "error",
      "message": "Failed to connect to API service",
      "context": {
        "endpoint": "/api/data",
        "statusCode": 503,
        "userId": "user-123"
      },
      "stackTrace": [
        "at ApiService.connect (line 45)",
        "at DataManager.fetch (line 120)"
      ]
    }
  ],
  "sentAt": "2026-01-07T12:00:05Z"
}
```

#### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `batchId` | string | Yes | Unique identifier for this batch of events (UUID recommended) |
| `deviceInfo` | object | Yes | Information about the device/client |
| `deviceInfo.deviceId` | string | Yes | Unique identifier for the device (persist across sessions) |
| `deviceInfo.deviceModel` | string | Yes | Device model (e.g., "iPhone 15 Pro", "Windows 11 PC") |
| `deviceInfo.osVersion` | string | Yes | Operating system version |
| `deviceInfo.appVersion` | string | Yes | Application version (semantic versioning recommended) |
| `deviceInfo.buildNumber` | string | No | Build number if applicable |
| `deviceInfo.platform` | string | Yes | Platform: `ios`, `android`, `web`, `chrome`, `windows`, `macos` |
| `appIdentifier` | string | Yes | Must match `X-App-Identifier` header |
| `environment` | string | Yes | `production`, `staging`, or `development` |
| `isTest` | boolean | No | Set to `true` for test/development data (filtered from production dashboards by default) |
| `events` | array | Yes | Array of event objects (max 100 per request) |
| `events[].id` | string | Yes | Unique identifier for this event |
| `events[].timestamp` | string | Yes | ISO 8601 timestamp when event occurred |
| `events[].level` | string | Yes | Event level (see Event Levels below) |
| `events[].message` | string | Yes | Human-readable event message |
| `events[].context` | object | No | Additional context data (any JSON object) |
| `events[].stackTrace` | array | No | Stack trace as array of strings (for crashes/errors) |
| `sentAt` | string | Yes | ISO 8601 timestamp when batch was sent |

#### Event Levels

| Level | Description | When to Use |
|-------|-------------|-------------|
| `crash` | Application crash | Unhandled exceptions, fatal errors |
| `error` | Error condition | Caught exceptions, failed operations |
| `warning` | Warning condition | Degraded performance, deprecation notices |
| `networkError` | Network-related error | API failures, timeouts, connectivity issues |
| `info` | Informational | User actions, feature usage, milestones |
| `heartbeat` | Health check | Periodic "I'm alive" signals |

#### Response

**Success (200 OK):**
```json
{
  "success": true,
  "batchId": "uuid-batch-identifier",
  "eventsReceived": 1
}
```

**Error (4xx/5xx):**
```json
{
  "success": false,
  "error": "Invalid API key",
  "code": "AUTH_INVALID_KEY"
}
```

---

### 2. Get Health Status

Get the current health status for a specific sub-product.

**Endpoint:** `GET /v1/health/status/{appIdentifier}`

#### Response

```json
{
  "appIdentifier": "myapp-ios",
  "status": "healthy",
  "healthScore": 95,
  "last24Hours": {
    "crashes": 2,
    "errors": 15,
    "warnings": 45,
    "networkErrors": 8,
    "totalEvents": 1250,
    "activeDevices": 342
  },
  "lastEventAt": "2026-01-07T11:55:00Z"
}
```

---

## Implementation Guide

### Best Practices

1. **Batch Events:** Collect events locally and send in batches (every 30-60 seconds or when 10+ events accumulate)

2. **Persist Device ID:** Generate a unique device ID on first launch and persist it:
   - iOS: Store in Keychain
   - Android: Store in SharedPreferences or Keystore
   - Web: Store in localStorage
   - Desktop: Store in app data folder

3. **Offline Support:** Queue events locally when offline and send when connectivity returns

4. **Retry Logic:** Implement exponential backoff for failed requests (1s, 2s, 4s, 8s, max 60s)

5. **Don't Block UI:** Send events asynchronously, never block the main thread

### Crash Handling

#### iOS (Swift)
```swift
// In AppDelegate or SceneDelegate
NSSetUncaughtExceptionHandler { exception in
    VitalyticsSDK.shared.logCrash(
        message: exception.reason ?? "Unknown crash",
        stackTrace: exception.callStackSymbols
    )
}
```

#### Android (Kotlin)
```kotlin
Thread.setDefaultUncaughtExceptionHandler { thread, throwable ->
    VitalyticsSDK.logCrash(
        message = throwable.message ?: "Unknown crash",
        stackTrace = throwable.stackTrace.map { it.toString() }
    )
}
```

#### JavaScript/TypeScript (Web/Chrome/Node)
```javascript
window.addEventListener('error', (event) => {
    Vitalytics.logError({
        message: event.message,
        context: {
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno
        }
    });
});

window.addEventListener('unhandledrejection', (event) => {
    Vitalytics.logError({
        message: `Unhandled Promise: ${event.reason}`,
        level: 'error'
    });
});
```

### Sample SDK Implementation

Here's a minimal SDK implementation in TypeScript:

```typescript
interface DeviceInfo {
  deviceId: string;
  deviceModel: string;
  osVersion: string;
  appVersion: string;
  platform: string;
}

interface HealthEvent {
  id: string;
  timestamp: string;
  level: 'crash' | 'error' | 'warning' | 'networkError' | 'info' | 'heartbeat';
  message: string;
  context?: Record<string, any>;
  stackTrace?: string[];
}

class VitalyticsSDK {
  private apiKey: string;
  private appIdentifier: string;
  private baseUrl: string;
  private deviceInfo: DeviceInfo;
  private eventQueue: HealthEvent[] = [];
  private environment: string;
  private isTest: boolean;

  constructor(config: {
    apiKey: string;
    appIdentifier: string;
    baseUrl: string;
    deviceInfo: DeviceInfo;
    environment?: string;
    isTest?: boolean;
  }) {
    this.apiKey = config.apiKey;
    this.appIdentifier = config.appIdentifier;
    this.baseUrl = config.baseUrl;
    this.deviceInfo = config.deviceInfo;
    this.environment = config.environment || 'production';
    this.isTest = config.isTest || false;

    // Flush events periodically
    setInterval(() => this.flush(), 30000);
  }

  log(level: HealthEvent['level'], message: string, context?: Record<string, any>) {
    this.eventQueue.push({
      id: this.generateId(),
      timestamp: new Date().toISOString(),
      level,
      message,
      context
    });

    // Auto-flush on crash/error or when queue is large
    if (level === 'crash' || level === 'error' || this.eventQueue.length >= 10) {
      this.flush();
    }
  }

  async flush() {
    if (this.eventQueue.length === 0) return;

    const events = [...this.eventQueue];
    this.eventQueue = [];

    try {
      const response = await fetch(`${this.baseUrl}/api/v1/health/events`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-Key': this.apiKey,
          'X-App-Identifier': this.appIdentifier
        },
        body: JSON.stringify({
          batchId: this.generateId(),
          deviceInfo: this.deviceInfo,
          appIdentifier: this.appIdentifier,
          environment: this.environment,
          isTest: this.isTest,
          events,
          sentAt: new Date().toISOString()
        })
      });

      if (!response.ok) {
        // Re-queue events on failure
        this.eventQueue.push(...events);
      }
    } catch (error) {
      // Re-queue events on network failure
      this.eventQueue.push(...events);
    }
  }

  private generateId(): string {
    return 'evt-' + Math.random().toString(36).substr(2, 9);
  }
}

// Usage
const vitalytics = new VitalyticsSDK({
  apiKey: 'your-api-key-here',
  appIdentifier: 'myapp-web',
  baseUrl: 'https://your-vitalytics-server.com',
  deviceInfo: {
    deviceId: localStorage.getItem('deviceId') || generateDeviceId(),
    deviceModel: navigator.userAgent,
    osVersion: navigator.platform,
    appVersion: '1.0.0',
    platform: 'web'
  }
});

// Log events
vitalytics.log('info', 'User started session');
vitalytics.log('error', 'Failed to load data', { errorCode: 'LOAD_FAILED' });
vitalytics.log('networkError', 'API timeout', { endpoint: '/api/data', timeout: 30000 });
```

---

## Rate Limits

| Limit | Value |
|-------|-------|
| Requests per minute | 1000 |
| Events per request | 100 |
| Max request body size | 1 MB |

---

## Error Codes

| Code | Description |
|------|-------------|
| `AUTH_MISSING_KEY` | X-API-Key header is missing |
| `AUTH_INVALID_KEY` | API key is invalid or revoked |
| `AUTH_MISSING_IDENTIFIER` | X-App-Identifier header is missing |
| `AUTH_IDENTIFIER_MISMATCH` | API key doesn't match app identifier |
| `VALIDATION_ERROR` | Request body validation failed |
| `RATE_LIMIT_EXCEEDED` | Too many requests |
| `SERVER_ERROR` | Internal server error |

---

## Changelog

### v1.0 (January 2026)
- Initial release
- Event submission endpoint
- Status endpoint
- Multi-product support
