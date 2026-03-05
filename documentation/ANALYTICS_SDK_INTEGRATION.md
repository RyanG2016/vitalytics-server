# Vitalytics Analytics SDK Integration Guide

This document explains how to integrate Vitalytics Analytics tracking into your applications. Analytics tracking is **separate from health monitoring** - it focuses on user behavior analysis rather than error/crash reporting.

---

## Table of Contents

1. [Overview](#overview)
2. [Key Concepts](#key-concepts)
3. [API Specification](#api-specification)
4. [Event Categories](#event-categories)
5. [Platform Integration Guides](#platform-integration-guides)
   - [JavaScript (Web/Chrome Extensions)](#javascript-webchrome-extensions)
   - [Swift (iOS/macOS)](#swift-iosmacos)
   - [Kotlin (Android)](#kotlin-android)
   - [Laravel (PHP Server)](#laravel-php-server)
   - [.NET (Windows)](#net-windows)
6. [Auto-Tracking](#auto-tracking)
7. [Declarative Tracking](#declarative-tracking)
8. [Best Practices](#best-practices)
9. [Privacy & Consent](#privacy--consent)

---

## Overview

Vitalytics Analytics helps you understand how users interact with your applications by tracking:

- **Screen/Page Views**: What screens users visit and how long they stay
- **User Actions**: Button clicks, form submissions, feature usage
- **Navigation Patterns**: How users flow through your app
- **Engagement Metrics**: Session duration, screens per session, return visits

### Analytics vs Health Monitoring

| Feature | Health Monitoring | Analytics |
|---------|-------------------|-----------|
| Purpose | Error tracking, crashes, uptime | User behavior analysis |
| Event Types | error, warning, crash, heartbeat | screen_viewed, button_clicked, etc. |
| Data Focus | What went wrong | What users are doing |
| Default State | Enabled | **Disabled** (opt-in) |

### Shared Configuration

**Analytics uses the same configuration as Health Monitoring.** You don't need separate credentials:

- **Same Base URL** - Your Vitalytics server URL (e.g., `https://vitalytics.yourserver.com`)
- **Same API Key** - The API key you're already using for health monitoring
- **Same App Identifier** - The app identifier (e.g., `myapp-laravel`, `myapp-android`)

If you already have health monitoring set up, simply reuse those configuration values for analytics. The server uses the same authentication and organizes events by app identifier.

---

## Key Concepts

### Privacy-First Design

Analytics is **disabled by default**. You must explicitly enable it in your SDK configuration. This ensures compliance with privacy expectations - users should opt-in to behavior tracking.

```javascript
// Analytics is OFF by default
const analytics = new VitalyticsAnalytics({
    enabled: false,  // Default - no data collected
    // ...
});

// Enable after user consent
analytics.setEnabled(true);
```

### Test Mode

Like health monitoring, analytics supports a `isTest` flag. Use this during development to:
- Keep test data separate from production
- Verify integration without polluting real metrics
- Filter test events in the dashboard

### Session Tracking

The SDK automatically manages sessions:
- New session starts when app launches or after 30 minutes of inactivity
- Session includes: duration, event count, screens viewed
- Sessions help analyze user engagement patterns

### Batching

Events are queued locally and sent in batches to:
- Reduce network requests
- Handle offline scenarios
- Minimize battery/performance impact

Default: Events flush every 30 seconds or when queue reaches 20 events.

### Timestamps (Important!)

**All timestamps MUST be in UTC (GMT+0).** The server stores all timestamps in UTC and converts to local timezone only for display.

| Field | Format | Example |
|-------|--------|---------|
| `timestamp` | ISO 8601 UTC | `2026-01-09T14:30:00Z` |
| `sentAt` | ISO 8601 UTC | `2026-01-09T14:30:05Z` |

**Why UTC?**
- Consistent storage across all timezones
- Accurate time-based analytics
- Proper session duration calculations
- Correct event ordering in user journeys

**Common Mistakes:**
```
❌ "2026-01-09T08:30:00-06:00"  // Local timezone offset
❌ "2026-01-09T08:30:00"         // No timezone indicator (ambiguous)
❌ "Jan 9, 2026 8:30 AM"         // Wrong format

✅ "2026-01-09T14:30:00Z"        // Correct: UTC with Z suffix
✅ "2026-01-09T14:30:00.000Z"    // Also correct: with milliseconds
```

---

## API Specification

### Authentication

Analytics uses the **same X-API-Key** as health monitoring. No separate token needed.

### Endpoint

```
POST https://your-vitalytics-server.com/api/v1/analytics/events
```

### Headers

```
X-API-Key: your-api-key
Content-Type: application/json
```

### Request Body

```json
{
    "batchId": "uuid-v4",
    "appIdentifier": "myapp-chrome",
    "deviceInfo": {
        "deviceId": "persistent-uuid",
        "deviceModel": "Chrome 120",
        "platform": "chrome",
        "osVersion": "Windows 11",
        "appVersion": "1.2.0",
        "screenResolution": "1920x1080",
        "language": "en-US"
    },
    "sessionId": "session-uuid",
    "anonymousUserId": "anon-user-uuid",
    "isTest": false,
    "sentAt": "2026-01-09T12:00:00Z",
    "events": [
        {
            "id": "event-uuid",
            "timestamp": "2026-01-09T11:59:45Z",
            "name": "screen_viewed",
            "category": "navigation",
            "screen": "Dashboard",
            "properties": {
                "source": "sidebar_link"
            },
            "duration": 5000,
            "referrer": "Settings"
        }
    ]
}
```

### Response

```json
{
    "success": true,
    "batchId": "uuid-v4",
    "eventsReceived": 5
}
```

### Rate Limits

- 1000 events per minute per app
- 100 events per batch maximum

---

## Event Categories

Use consistent categories across your apps for better dashboard analytics.

### Standard Categories

| Category | Description | Example Events |
|----------|-------------|----------------|
| `navigation` | Screen/page transitions | screen_viewed, page_viewed, tab_switched |
| `interaction` | User interactions | button_clicked, link_clicked, menu_opened |
| `form` | Form-related actions | form_submitted, field_focused, validation_error |
| `feature` | Feature usage | feature_enabled, feature_used, setting_changed |
| `content` | Content engagement | content_viewed, content_shared, content_downloaded |
| `search` | Search behavior | search_performed, search_result_clicked |
| `session` | Session lifecycle | session_started, session_ended, app_backgrounded |
| `conversion` | Goal completions | signup_completed, purchase_completed |

### Standard Event Names

#### Navigation Events
- `screen_viewed` - User viewed a screen (mobile)
- `page_viewed` - User viewed a page (web)
- `tab_switched` - User switched tabs
- `modal_opened` / `modal_closed`
- `drawer_opened` / `drawer_closed`

#### Interaction Events
- `button_clicked` - Button press/click
- `link_clicked` - Link navigation
- `menu_item_selected` - Menu selection
- `toggle_switched` - Toggle/switch changed
- `slider_changed` - Slider value changed

#### Form Events
- `form_started` - User began filling form
- `form_submitted` - Form submission attempted
- `form_completed` - Form successfully submitted
- `form_abandoned` - User left form incomplete
- `field_focused` - Input field received focus

#### Feature Events
- `feature_used` - Core feature activated
- `setting_changed` - User modified settings
- `preference_updated` - Preference changed
- `export_requested` - Data export initiated

---

## Screen Tracking (Critical)

**Screen tracking is essential for understanding user journeys.** Without screen tracking, you can see individual events but cannot analyze:
- Which screens users visit most
- How users navigate through your app
- Where users drop off
- Time spent on each screen

### How Screen Tracking Works

1. Call `trackScreen('ScreenName')` when a user navigates to a new screen/view
2. The SDK automatically:
   - Records a `screen_viewed` event
   - Tracks time spent on the previous screen
   - Associates subsequent events with the current screen

### When to Call trackScreen()

| Platform | When to Track |
|----------|---------------|
| **Web SPA** | Route changes, modal opens, tab switches |
| **Chrome Extension** | Popup opens, sidebar opens, view changes within popup/sidebar |
| **iOS/Android** | ViewController/Activity appears, fragment changes |
| **Desktop** | Window/form opens, tab changes, navigation events |

### Screen Naming Conventions

Use consistent, descriptive names:

```
✅ Good: "Dashboard", "Settings", "UserProfile", "PromptEditor"
❌ Bad: "Screen1", "view", "main", "ViewController1"
```

### Example Flow

```
User opens app → trackScreen('Home')
User clicks Settings → trackScreen('Settings')
User changes theme → trackEvent('theme_changed', 'settings', {theme: 'dark'})
User goes back → trackScreen('Home')
```

This produces:
- 3 screen_viewed events (Home, Settings, Home)
- 1 theme_changed event (associated with Settings screen)
- Automatic duration tracking for each screen

---

## Platform Integration Guides

### JavaScript (Web/Chrome Extensions)

#### SDK Implementation

```javascript
class VitalyticsAnalytics {
    constructor(config) {
        this.apiKey = config.apiKey;
        this.appIdentifier = config.appIdentifier;
        this.enabled = config.enabled ?? false; // Disabled by default
        this.isTest = config.isTest ?? false;
        this.flushInterval = config.flushInterval ?? 30000;
        this.maxQueueSize = config.maxQueueSize ?? 20;

        this.eventQueue = [];
        this.sessionId = this.generateUUID();
        this.sessionStartTime = Date.now();
        this.deviceInfo = config.deviceInfo || this.detectDeviceInfo();
        this.anonymousUserId = config.anonymousUserId || this.getOrCreateAnonymousId();

        this.currentScreen = null;
        this.screenStartTime = null;

        if (this.enabled) {
            this.startFlushTimer();
            this.trackSessionStart();
        }
    }

    // Enable/disable analytics (call after user consent)
    setEnabled(enabled) {
        this.enabled = enabled;
        if (enabled) {
            this.startFlushTimer();
            this.trackSessionStart();
        } else {
            this.stopFlushTimer();
            this.eventQueue = [];
        }
    }

    // Track screen/page view
    trackScreen(screenName, properties = {}) {
        if (!this.enabled) return;

        // Track time on previous screen
        if (this.currentScreen && this.screenStartTime) {
            const duration = Date.now() - this.screenStartTime;
            this.queueEvent({
                name: 'screen_duration',
                category: 'navigation',
                screen: this.currentScreen,
                duration: duration
            });
        }

        this.currentScreen = screenName;
        this.screenStartTime = Date.now();

        this.queueEvent({
            name: 'screen_viewed',
            category: 'navigation',
            screen: screenName,
            properties: properties,
            referrer: this.currentScreen
        });
    }

    // Track user interaction
    trackEvent(eventName, category, properties = {}) {
        if (!this.enabled) return;

        this.queueEvent({
            name: eventName,
            category: category,
            screen: this.currentScreen,
            properties: properties
        });
    }

    // Track button click
    trackClick(elementId, properties = {}) {
        this.trackEvent('button_clicked', 'interaction', {
            element: elementId,
            ...properties
        });
    }

    // Track feature usage
    trackFeature(featureName, properties = {}) {
        this.trackEvent('feature_used', 'feature', {
            feature: featureName,
            ...properties
        });
    }

    // Queue event for batching
    // IMPORTANT: timestamp must be UTC - toISOString() returns UTC with Z suffix
    queueEvent(event) {
        this.eventQueue.push({
            id: this.generateUUID(),
            timestamp: new Date().toISOString(), // Always UTC (e.g., "2026-01-09T14:30:00.000Z")
            ...event
        });

        if (this.eventQueue.length >= this.maxQueueSize) {
            this.flush();
        }
    }

    // Send queued events
    async flush() {
        if (!this.enabled || this.eventQueue.length === 0) return;

        const events = [...this.eventQueue];
        this.eventQueue = [];

        const batch = {
            batchId: this.generateUUID(),
            appIdentifier: this.appIdentifier,
            deviceInfo: this.deviceInfo,
            sessionId: this.sessionId,
            anonymousUserId: this.anonymousUserId,
            isTest: this.isTest,
            sentAt: new Date().toISOString(),
            events: events
        };

        try {
            const response = await fetch('https://your-vitalytics-server.com/api/v1/analytics/events', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': this.apiKey
                },
                body: JSON.stringify(batch)
            });

            if (!response.ok) {
                // Re-queue on failure
                this.eventQueue = [...events, ...this.eventQueue];
            }
        } catch (error) {
            // Re-queue on network error
            this.eventQueue = [...events, ...this.eventQueue];
            console.error('Analytics flush failed:', error);
        }
    }

    // Session tracking
    trackSessionStart() {
        this.queueEvent({
            name: 'session_started',
            category: 'session',
            properties: {
                sessionId: this.sessionId
            }
        });
    }

    trackSessionEnd() {
        const duration = Date.now() - this.sessionStartTime;
        this.queueEvent({
            name: 'session_ended',
            category: 'session',
            duration: duration,
            properties: {
                sessionId: this.sessionId
            }
        });
        this.flush(); // Immediate flush on session end
    }

    // Utility methods
    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    detectDeviceInfo() {
        return {
            deviceId: this.getOrCreateDeviceId(),
            deviceModel: navigator.userAgent,
            platform: this.detectPlatform(),
            osVersion: navigator.platform,
            appVersion: '1.0.0', // Set from your app config
            screenResolution: `${window.screen.width}x${window.screen.height}`,
            language: navigator.language
        };
    }

    detectPlatform() {
        if (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.id) {
            return 'chrome';
        }
        return 'web';
    }

    getOrCreateDeviceId() {
        let deviceId = localStorage.getItem('vitalytics_device_id');
        if (!deviceId) {
            deviceId = 'dev-' + this.generateUUID();
            localStorage.setItem('vitalytics_device_id', deviceId);
        }
        return deviceId;
    }

    getOrCreateAnonymousId() {
        let anonId = localStorage.getItem('vitalytics_anon_id');
        if (!anonId) {
            anonId = 'anon-' + this.generateUUID();
            localStorage.setItem('vitalytics_anon_id', anonId);
        }
        return anonId;
    }

    startFlushTimer() {
        this.flushTimer = setInterval(() => this.flush(), this.flushInterval);
    }

    stopFlushTimer() {
        if (this.flushTimer) {
            clearInterval(this.flushTimer);
            this.flushTimer = null;
        }
    }
}
```

#### Usage Example

```javascript
// Initialize (disabled by default)
const analytics = new VitalyticsAnalytics({
    apiKey: 'your-api-key',
    appIdentifier: 'myapp-chrome',
    enabled: false, // Disabled until user consents
    isTest: false   // Set true during development
});

// After user consents to analytics
analytics.setEnabled(true);

// Track screens
analytics.trackScreen('Dashboard');
analytics.trackScreen('Settings', { source: 'menu' });

// Track interactions
analytics.trackClick('save-button', { formId: 'profile-form' });
analytics.trackEvent('menu_opened', 'interaction', { menuName: 'settings' });

// Track features
analytics.trackFeature('dark-mode', { enabled: true });
analytics.trackFeature('export', { format: 'csv' });

// Track form events
analytics.trackEvent('form_submitted', 'form', {
    formName: 'contact',
    fieldCount: 5
});

// Flush on page unload
window.addEventListener('beforeunload', () => {
    analytics.trackSessionEnd();
});
```

#### Chrome Extension Specific

```javascript
// Use chrome.storage for persistence
class ChromeAnalytics extends VitalyticsAnalytics {
    async getOrCreateDeviceId() {
        const result = await chrome.storage.local.get('vitalytics_device_id');
        if (result.vitalytics_device_id) {
            return result.vitalytics_device_id;
        }
        const deviceId = 'chrome-ext-' + this.generateUUID();
        await chrome.storage.local.set({ vitalytics_device_id: deviceId });
        return deviceId;
    }

    async getOrCreateAnonymousId() {
        const result = await chrome.storage.local.get('vitalytics_anon_id');
        if (result.vitalytics_anon_id) {
            return result.vitalytics_anon_id;
        }
        const anonId = 'anon-' + this.generateUUID();
        await chrome.storage.local.set({ vitalytics_anon_id: anonId });
        return anonId;
    }
}
```

#### Chrome Extension Screen Tracking (Important!)

**Screen tracking in Chrome extensions requires explicit calls.** Unlike web apps with URL routing, extensions don't have automatic navigation detection.

##### When to Track Screens

```javascript
// 1. POPUP - When popup opens
// In popup.js or when popup HTML loads:
document.addEventListener('DOMContentLoaded', () => {
    analytics.trackScreen('Popup');
});

// 2. SIDEBAR - When sidebar opens/initializes
function initSidebar() {
    analytics.trackScreen('Sidebar');
    // ... rest of sidebar init
}

// 3. VIEW CHANGES - When switching views within popup/sidebar
function showPromptList() {
    analytics.trackScreen('PromptList');
    // ... show prompt list UI
}

function showPromptEditor(promptId) {
    analytics.trackScreen('PromptEditor', { promptId });
    // ... show editor UI
}

function showSettings() {
    analytics.trackScreen('Settings');
    // ... show settings UI
}

// 4. OPTIONS PAGE - When options page loads
// In options.js:
analytics.trackScreen('Options');

// 5. CONTENT SCRIPT VIEWS - If you inject UI into pages
function showOverlay() {
    analytics.trackScreen('ContentOverlay', {
        hostPage: window.location.hostname
    });
}
```

##### Complete Chrome Extension Example

```javascript
// analytics-integration.js - Import in all extension contexts

const analytics = new ChromeAnalytics({
    apiKey: 'your-api-key',
    appIdentifier: 'myapp-chrome',
    enabled: false,
    isTest: true
});

// Screen tracking helper with consistent naming
const Screens = {
    POPUP: 'Popup',
    SIDEBAR: 'Sidebar',
    PROMPT_LIST: 'PromptList',
    PROMPT_EDITOR: 'PromptEditor',
    SETTINGS: 'Settings',
    OPTIONS: 'Options',
    ONBOARDING: 'Onboarding'
};

// Track screen with automatic event association
function navigateTo(screen, properties = {}) {
    analytics.trackScreen(screen, properties);
}

// Usage in your extension:
// When sidebar opens:
navigateTo(Screens.SIDEBAR);

// When user selects a prompt:
navigateTo(Screens.PROMPT_EDITOR, { promptId: '123' });
analytics.trackEvent('prompt_selected', 'interaction', { promptId: '123' });

// When user opens settings:
navigateTo(Screens.SETTINGS);
```

##### Service Worker / Background Script

```javascript
// background.js / service-worker.js

// Track extension lifecycle
chrome.runtime.onInstalled.addListener((details) => {
    if (details.reason === 'install') {
        analytics.trackScreen('Onboarding');
        analytics.trackEvent('extension_installed', 'lifecycle');
    } else if (details.reason === 'update') {
        analytics.trackEvent('extension_updated', 'lifecycle', {
            previousVersion: details.previousVersion
        });
    }
});

// Track when extension icon is clicked
chrome.action.onClicked.addListener((tab) => {
    analytics.trackScreen('Popup');
    analytics.trackEvent('extension_icon_clicked', 'interaction');
});
```

##### Message Passing for Analytics

```javascript
// If analytics runs in background, use message passing:

// From content script or popup:
chrome.runtime.sendMessage({
    action: 'trackScreen',
    screen: 'Sidebar',
    properties: { pimsName: 'detected-pims' }
});

chrome.runtime.sendMessage({
    action: 'trackEvent',
    eventType: 'prompt_selected',
    properties: { promptId: '67' }
});

// In background/service-worker:
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    if (message.action === 'trackScreen') {
        analytics.trackScreen(message.screen, message.properties);
    } else if (message.action === 'trackEvent') {
        analytics.trackEvent(message.eventType, 'interaction', message.properties);
    }
});
```

---

### Swift (iOS/macOS)

```swift
import Foundation

class VitalyticsAnalytics {
    static let shared = VitalyticsAnalytics()

    private var apiKey: String = ""
    private var appIdentifier: String = ""
    private var enabled: Bool = false // Disabled by default
    private var isTest: Bool = false

    private var eventQueue: [[String: Any]] = []
    private var sessionId: String = UUID().uuidString
    private var sessionStartTime: Date = Date()
    private var currentScreen: String?
    private var screenStartTime: Date?

    private var flushTimer: Timer?

    func configure(apiKey: String, appIdentifier: String, isTest: Bool = false) {
        self.apiKey = apiKey
        self.appIdentifier = appIdentifier
        self.isTest = isTest
        // Note: enabled remains false until setEnabled(true) is called
    }

    func setEnabled(_ enabled: Bool) {
        self.enabled = enabled
        if enabled {
            startSession()
        } else {
            stopSession()
            eventQueue.removeAll()
        }
    }

    func trackScreen(_ screenName: String, properties: [String: Any] = [:]) {
        guard enabled else { return }

        // Track duration on previous screen
        if let current = currentScreen, let start = screenStartTime {
            let duration = Int(Date().timeIntervalSince(start) * 1000)
            queueEvent(name: "screen_duration", category: "navigation",
                      screen: current, duration: duration)
        }

        currentScreen = screenName
        screenStartTime = Date()

        queueEvent(name: "screen_viewed", category: "navigation",
                  screen: screenName, properties: properties)
    }

    func trackEvent(_ name: String, category: String, properties: [String: Any] = [:]) {
        guard enabled else { return }
        queueEvent(name: name, category: category,
                  screen: currentScreen, properties: properties)
    }

    func trackClick(_ elementId: String, properties: [String: Any] = [:]) {
        var props = properties
        props["element"] = elementId
        trackEvent("button_clicked", category: "interaction", properties: props)
    }

    func trackFeature(_ featureName: String, properties: [String: Any] = [:]) {
        var props = properties
        props["feature"] = featureName
        trackEvent("feature_used", category: "feature", properties: props)
    }

    // IMPORTANT: All timestamps must be UTC
    private func queueEvent(name: String, category: String, screen: String? = nil,
                           properties: [String: Any]? = nil, duration: Int? = nil) {
        let formatter = ISO8601DateFormatter()
        formatter.timeZone = TimeZone(identifier: "UTC") // Ensure UTC

        var event: [String: Any] = [
            "id": UUID().uuidString,
            "timestamp": formatter.string(from: Date()), // UTC timestamp
            "name": name,
            "category": category
        ]
        if let screen = screen { event["screen"] = screen }
        if let props = properties { event["properties"] = props }
        if let dur = duration { event["duration"] = dur }

        eventQueue.append(event)

        if eventQueue.count >= 20 {
            flush()
        }
    }

    func flush() {
        guard enabled, !eventQueue.isEmpty else { return }

        let events = eventQueue
        eventQueue.removeAll()

        let batch: [String: Any] = [
            "batchId": UUID().uuidString,
            "appIdentifier": appIdentifier,
            "deviceInfo": getDeviceInfo(),
            "sessionId": sessionId,
            "anonymousUserId": getAnonymousUserId(),
            "isTest": isTest,
            "sentAt": ISO8601DateFormatter().string(from: Date()),
            "events": events
        ]

        sendBatch(batch)
    }

    private func sendBatch(_ batch: [String: Any]) {
        guard let url = URL(string: "https://your-vitalytics-server.com/api/v1/analytics/events"),
              let body = try? JSONSerialization.data(withJSONObject: batch) else { return }

        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue(apiKey, forHTTPHeaderField: "X-API-Key")
        request.httpBody = body

        URLSession.shared.dataTask(with: request) { _, response, error in
            if error != nil {
                // Re-queue events on failure
                if let events = batch["events"] as? [[String: Any]] {
                    self.eventQueue.insert(contentsOf: events, at: 0)
                }
            }
        }.resume()
    }

    private func getDeviceInfo() -> [String: Any] {
        return [
            "deviceId": getDeviceId(),
            "deviceModel": UIDevice.current.model,
            "platform": "ios",
            "osVersion": UIDevice.current.systemVersion,
            "appVersion": Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String ?? "1.0",
            "screenResolution": "\(Int(UIScreen.main.bounds.width))x\(Int(UIScreen.main.bounds.height))",
            "language": Locale.current.language.languageCode?.identifier ?? "en"
        ]
    }

    private func getDeviceId() -> String {
        if let id = UserDefaults.standard.string(forKey: "vitalytics_device_id") {
            return id
        }
        let id = "dev-\(UUID().uuidString)"
        UserDefaults.standard.set(id, forKey: "vitalytics_device_id")
        return id
    }

    private func getAnonymousUserId() -> String {
        if let id = UserDefaults.standard.string(forKey: "vitalytics_anon_id") {
            return id
        }
        let id = "anon-\(UUID().uuidString)"
        UserDefaults.standard.set(id, forKey: "vitalytics_anon_id")
        return id
    }

    private func startSession() {
        sessionId = UUID().uuidString
        sessionStartTime = Date()
        queueEvent(name: "session_started", category: "session")

        flushTimer = Timer.scheduledTimer(withTimeInterval: 30, repeats: true) { _ in
            self.flush()
        }
    }

    private func stopSession() {
        flushTimer?.invalidate()
        flushTimer = nil
    }
}
```

#### Usage

```swift
// Configure (still disabled)
VitalyticsAnalytics.shared.configure(
    apiKey: "your-api-key",
    appIdentifier: "myapp-ios",
    isTest: true // Set false for production
)

// Enable after user consent
VitalyticsAnalytics.shared.setEnabled(true)

// Track screens
VitalyticsAnalytics.shared.trackScreen("HomeViewController")
VitalyticsAnalytics.shared.trackScreen("SettingsViewController", properties: ["source": "menu"])

// Track interactions
VitalyticsAnalytics.shared.trackClick("save-button")
VitalyticsAnalytics.shared.trackFeature("dark-mode", properties: ["enabled": true])
```

#### iOS/macOS Screen Tracking Best Practices

**Track screens in viewDidAppear (not viewDidLoad):**

```swift
class PatientViewController: UIViewController {
    override func viewDidAppear(_ animated: Bool) {
        super.viewDidAppear(animated)
        VitalyticsAnalytics.shared.trackScreen("PatientView")
    }
}

class RecordingViewController: UIViewController {
    var patientId: String?

    override func viewDidAppear(_ animated: Bool) {
        super.viewDidAppear(animated)
        VitalyticsAnalytics.shared.trackScreen("RecordingView", properties: [
            "patientId": patientId ?? ""
        ])
    }
}
```

**SwiftUI - Use onAppear modifier:**

```swift
struct PatientView: View {
    var body: some View {
        VStack {
            // Content
        }
        .onAppear {
            VitalyticsAnalytics.shared.trackScreen("PatientView")
        }
    }
}

struct SettingsView: View {
    var body: some View {
        NavigationStack {
            List {
                // Settings
            }
        }
        .onAppear {
            VitalyticsAnalytics.shared.trackScreen("Settings")
        }
    }
}
```

**Track modals and sheets:**

```swift
.sheet(isPresented: $showingProfile) {
    ProfileView()
        .onAppear {
            VitalyticsAnalytics.shared.trackScreen("ProfileSheet")
        }
}
```

**Complete user journey example:**

```swift
// User opens app
VitalyticsAnalytics.shared.trackScreen("Home")

// User taps on patient
VitalyticsAnalytics.shared.trackScreen("PatientDetail", properties: ["patientId": "123"])
VitalyticsAnalytics.shared.trackEvent("patient_loaded", category: "interaction")

// User selects a template
VitalyticsAnalytics.shared.trackEvent("template_selected", category: "interaction", properties: [
    "templateId": "soap-note",
    "templateName": "SOAP Note"
])

// User starts recording
VitalyticsAnalytics.shared.trackScreen("Recording")
VitalyticsAnalytics.shared.trackEvent("recording_started", category: "feature")

// User stops recording
VitalyticsAnalytics.shared.trackEvent("recording_stopped", category: "feature", properties: [
    "durationSeconds": 125
])

// User reviews generated note
VitalyticsAnalytics.shared.trackScreen("NoteReview")
VitalyticsAnalytics.shared.trackEvent("note_generated", category: "feature")
```

---

### Kotlin (Android)

```kotlin
import android.content.Context
import android.content.SharedPreferences
import android.os.Build
import kotlinx.coroutines.*
import org.json.JSONArray
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL
import java.text.SimpleDateFormat
import java.util.*

object VitalyticsAnalytics {
    private lateinit var context: Context
    private var baseUrl: String = ""
    private var apiKey: String = ""
    private var appIdentifier: String = ""
    private var enabled: Boolean = false // Disabled by default
    private var isTest: Boolean = false

    private val eventQueue = mutableListOf<JSONObject>()
    private var sessionId: String = UUID.randomUUID().toString()
    private var sessionStartTime: Long = System.currentTimeMillis()
    private var currentScreen: String? = null
    private var screenStartTime: Long? = null

    private var flushJob: Job? = null
    private val scope = CoroutineScope(Dispatchers.IO + SupervisorJob())

    private val prefs: SharedPreferences by lazy {
        context.getSharedPreferences("vitalytics_analytics", Context.MODE_PRIVATE)
    }

    fun initialize(
        context: Context,
        baseUrl: String,  // e.g., "https://vitalytics.yourserver.com"
        apiKey: String,
        appIdentifier: String,
        isTest: Boolean = false
    ) {
        this.context = context.applicationContext
        this.baseUrl = baseUrl.trimEnd('/')
        this.apiKey = apiKey
        this.appIdentifier = appIdentifier
        this.isTest = isTest
        // Note: enabled remains false until setEnabled(true) is called
    }

    fun setEnabled(enabled: Boolean) {
        this.enabled = enabled
        if (enabled) {
            startSession()
        } else {
            stopSession()
            eventQueue.clear()
        }
    }

    fun trackScreen(screenName: String, properties: Map<String, Any> = emptyMap()) {
        if (!enabled) return

        // Track duration on previous screen
        currentScreen?.let { current ->
            screenStartTime?.let { start ->
                val duration = System.currentTimeMillis() - start
                queueEvent("screen_duration", "navigation", current, duration = duration.toInt())
            }
        }

        currentScreen = screenName
        screenStartTime = System.currentTimeMillis()

        queueEvent("screen_viewed", "navigation", screenName, properties)
    }

    fun trackEvent(name: String, category: String, properties: Map<String, Any> = emptyMap()) {
        if (!enabled) return
        queueEvent(name, category, currentScreen, properties)
    }

    fun trackClick(elementId: String, properties: Map<String, Any> = emptyMap()) {
        trackEvent("button_clicked", "interaction", properties + ("element" to elementId))
    }

    fun trackFeature(featureName: String, properties: Map<String, Any> = emptyMap()) {
        trackEvent("feature_used", "feature", properties + ("feature" to featureName))
    }

    private fun queueEvent(
        name: String,
        category: String,
        screen: String? = null,
        properties: Map<String, Any>? = null,
        duration: Int? = null
    ) {
        val event = JSONObject().apply {
            put("id", UUID.randomUUID().toString())
            put("timestamp", getISOTimestamp())
            put("name", name)
            put("category", category)
            screen?.let { put("screen", it) }
            properties?.let { put("properties", JSONObject(it)) }
            duration?.let { put("duration", it) }
        }

        synchronized(eventQueue) {
            eventQueue.add(event)
            if (eventQueue.size >= 20) {
                flush()
            }
        }
    }

    fun flush() {
        if (!enabled || eventQueue.isEmpty()) return

        val events: List<JSONObject>
        synchronized(eventQueue) {
            events = eventQueue.toList()
            eventQueue.clear()
        }

        scope.launch {
            sendBatch(events)
        }
    }

    private suspend fun sendBatch(events: List<JSONObject>) {
        val batch = JSONObject().apply {
            put("batchId", UUID.randomUUID().toString())
            put("appIdentifier", appIdentifier)
            put("deviceInfo", getDeviceInfo())
            put("sessionId", sessionId)
            put("anonymousUserId", getAnonymousUserId())
            put("isTest", isTest)
            put("sentAt", getISOTimestamp())
            put("events", JSONArray(events))
        }

        try {
            val url = URL("$baseUrl/api/v1/analytics/events")
            val connection = url.openConnection() as HttpURLConnection
            connection.requestMethod = "POST"
            connection.setRequestProperty("Content-Type", "application/json")
            connection.setRequestProperty("X-API-Key", apiKey)
            connection.doOutput = true

            connection.outputStream.write(batch.toString().toByteArray())

            if (connection.responseCode !in 200..299) {
                // Re-queue on failure
                synchronized(eventQueue) {
                    eventQueue.addAll(0, events)
                }
            }
            connection.disconnect()
        } catch (e: Exception) {
            // Re-queue on error
            synchronized(eventQueue) {
                eventQueue.addAll(0, events)
            }
        }
    }

    private fun getDeviceInfo(): JSONObject {
        return JSONObject().apply {
            put("deviceId", getDeviceId())
            put("deviceModel", Build.MODEL)
            put("platform", "android")
            put("osVersion", Build.VERSION.RELEASE)
            put("appVersion", context.packageManager
                .getPackageInfo(context.packageName, 0).versionName)
            put("language", Locale.getDefault().language)
        }
    }

    private fun getDeviceId(): String {
        return prefs.getString("device_id", null) ?: run {
            val id = "dev-${UUID.randomUUID()}"
            prefs.edit().putString("device_id", id).apply()
            id
        }
    }

    private fun getAnonymousUserId(): String {
        return prefs.getString("anon_id", null) ?: run {
            val id = "anon-${UUID.randomUUID()}"
            prefs.edit().putString("anon_id", id).apply()
            id
        }
    }

    // IMPORTANT: All timestamps must be UTC (GMT+0)
    private fun getISOTimestamp(): String {
        return SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'", Locale.US).apply {
            timeZone = TimeZone.getTimeZone("UTC") // Must be UTC!
        }.format(Date())
    }

    private fun startSession() {
        sessionId = UUID.randomUUID().toString()
        sessionStartTime = System.currentTimeMillis()
        queueEvent("session_started", "session")

        flushJob = scope.launch {
            while (isActive) {
                delay(30_000)
                flush()
            }
        }
    }

    private fun stopSession() {
        flushJob?.cancel()
        flushJob = null
    }
}
```

#### Usage

```kotlin
// Initialize in Application class
class MyApp : Application() {
    override fun onCreate() {
        super.onCreate()
        // Use the same config as health monitoring - no separate credentials needed
        VitalyticsAnalytics.initialize(
            context = this,
            baseUrl = "https://vitalytics.yourserver.com",  // Same as health monitoring
            apiKey = "your-api-key",                        // Same API key as health
            appIdentifier = "myapp-android",                // Same app identifier as health
            isTest = BuildConfig.DEBUG
        )
    }
}

// Enable after user consent
VitalyticsAnalytics.setEnabled(true)

// Track screens
VitalyticsAnalytics.trackScreen("HomeActivity")
VitalyticsAnalytics.trackScreen("SettingsFragment", mapOf("source" to "menu"))

// Track interactions
VitalyticsAnalytics.trackClick("save-button")
VitalyticsAnalytics.trackFeature("dark-mode", mapOf("enabled" to true))
```

#### Android Screen Tracking Best Practices

**Track screens in onResume (not onCreate):**

```kotlin
class PatientActivity : AppCompatActivity() {
    override fun onResume() {
        super.onResume()
        VitalyticsAnalytics.trackScreen("PatientView")
    }
}

class RecordingActivity : AppCompatActivity() {
    override fun onResume() {
        super.onResume()
        VitalyticsAnalytics.trackScreen("RecordingView", mapOf(
            "patientId" to intent.getStringExtra("PATIENT_ID")
        ))
    }
}
```

**Fragment screen tracking:**

```kotlin
class SettingsFragment : Fragment() {
    override fun onResume() {
        super.onResume()
        VitalyticsAnalytics.trackScreen("Settings")
    }
}

class ProfileFragment : Fragment() {
    override fun onResume() {
        super.onResume()
        VitalyticsAnalytics.trackScreen("Profile", mapOf(
            "userId" to arguments?.getString("USER_ID")
        ))
    }
}
```

**Jetpack Compose - Use LaunchedEffect or DisposableEffect:**

```kotlin
@Composable
fun PatientScreen(patientId: String) {
    LaunchedEffect(Unit) {
        VitalyticsAnalytics.trackScreen("PatientView", mapOf("patientId" to patientId))
    }

    Column {
        // Screen content
    }
}

@Composable
fun SettingsScreen() {
    LaunchedEffect(Unit) {
        VitalyticsAnalytics.trackScreen("Settings")
    }

    Column {
        // Settings content
    }
}
```

**Track dialogs and bottom sheets:**

```kotlin
fun showTemplateDialog() {
    VitalyticsAnalytics.trackScreen("TemplateSelector")
    // Show dialog
}

fun showBottomSheet() {
    VitalyticsAnalytics.trackScreen("OptionsBottomSheet")
    // Show bottom sheet
}
```

**Complete user journey example:**

```kotlin
// User opens app - in MainActivity.onResume()
VitalyticsAnalytics.trackScreen("Home")

// User taps on patient - in PatientActivity.onResume()
VitalyticsAnalytics.trackScreen("PatientDetail", mapOf("patientId" to "123"))
VitalyticsAnalytics.trackEvent("patient_loaded", "interaction")

// User selects a template
VitalyticsAnalytics.trackEvent("template_selected", "interaction", mapOf(
    "templateId" to "soap-note",
    "templateName" to "SOAP Note"
))

// User starts recording - in RecordingActivity.onResume()
VitalyticsAnalytics.trackScreen("Recording")
VitalyticsAnalytics.trackEvent("recording_started", "feature")

// User stops recording
VitalyticsAnalytics.trackEvent("recording_stopped", "feature", mapOf(
    "durationSeconds" to 125
))

// User reviews note - in ReviewActivity.onResume()
VitalyticsAnalytics.trackScreen("NoteReview")
VitalyticsAnalytics.trackEvent("note_generated", "feature")
```

---

### Laravel (PHP Server)

For server-side tracking (API usage, background jobs, admin actions):

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class VitalyticsAnalytics
{
    private static ?self $instance = null;

    private string $baseUrl;
    private string $apiKey;
    private string $appIdentifier;
    private bool $enabled = false; // Disabled by default
    private bool $isTest = false;

    private array $eventQueue = [];
    private string $sessionId;
    private ?string $deviceId = null;

    private function __construct()
    {
        $this->sessionId = (string) Str::uuid();
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function configure(string $baseUrl, string $apiKey, string $appIdentifier, bool $isTest = false): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->appIdentifier = $appIdentifier;
        $this->isTest = $isTest;
        return $this;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function setDeviceId(string $deviceId): self
    {
        $this->deviceId = $deviceId;
        return $this;
    }

    private ?string $currentScreen = null;

    public function trackScreen(string $screenName, array $properties = []): self
    {
        $this->currentScreen = $screenName;
        return $this->trackEvent('screen_viewed', 'navigation', array_merge(
            ['screen' => $screenName],
            $properties
        ));
    }

    public function trackEvent(string $name, string $category, array $properties = []): self
    {
        if (!$this->enabled) {
            return $this;
        }

        // IMPORTANT: All timestamps must be UTC
        $event = [
            'id' => (string) Str::uuid(),
            'timestamp' => now()->utc()->toIso8601String(), // Must be UTC!
            'name' => $name,
            'category' => $category,
            'properties' => $properties ?: null,
        ];

        // Include current screen context if available
        if ($this->currentScreen) {
            $event['screen'] = $this->currentScreen;
        }

        $this->eventQueue[] = $event;

        if (count($this->eventQueue) >= 20) {
            $this->flush();
        }

        return $this;
    }

    public function trackApiCall(string $endpoint, string $method, int $statusCode, int $durationMs): self
    {
        return $this->trackEvent('api_called', 'api', [
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
        ]);
    }

    public function trackFeature(string $featureName, array $properties = []): self
    {
        return $this->trackEvent('feature_used', 'feature', array_merge(
            ['feature' => $featureName],
            $properties
        ));
    }

    public function trackJob(string $jobName, string $status, array $properties = []): self
    {
        return $this->trackEvent('job_' . $status, 'background', array_merge(
            ['job' => $jobName],
            $properties
        ));
    }

    public function flush(): bool
    {
        if (!$this->enabled || empty($this->eventQueue)) {
            return true;
        }

        $events = $this->eventQueue;
        $this->eventQueue = [];

        $batch = [
            'batchId' => (string) Str::uuid(),
            'appIdentifier' => $this->appIdentifier,
            'deviceInfo' => [
                'deviceId' => $this->deviceId ?? $this->getServerDeviceId(),
                'deviceModel' => gethostname(),
                'platform' => 'laravel',
                'osVersion' => PHP_OS,
                'appVersion' => config('app.version', '1.0.0'),
            ],
            'sessionId' => $this->sessionId,
            'isTest' => $this->isTest,
            'sentAt' => now()->utc()->toIso8601String(), // Must be UTC!
            'events' => $events,
        ];

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
            ])->post($this->baseUrl . '/api/v1/analytics/events', $batch);

            if (!$response->successful()) {
                // Re-queue on failure
                $this->eventQueue = array_merge($events, $this->eventQueue);
                Log::warning('Analytics flush failed', ['status' => $response->status()]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            // Re-queue on error
            $this->eventQueue = array_merge($events, $this->eventQueue);
            Log::warning('Analytics flush error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function getServerDeviceId(): string
    {
        $cacheFile = storage_path('framework/.vitalytics_device_id');

        if (file_exists($cacheFile)) {
            return trim(file_get_contents($cacheFile));
        }

        $deviceId = 'srv-' . Str::uuid();
        file_put_contents($cacheFile, $deviceId);
        return $deviceId;
    }

    public function __destruct()
    {
        $this->flush();
    }
}
```

#### Usage

```php
// In a service provider or bootstrap file (e.g., AppServiceProvider::boot())
VitalyticsAnalytics::instance()
    ->configure(
        config('vitalytics.base_url'),        // e.g., 'https://vitalytics.yourserver.com'
        config('vitalytics.api_key'),         // Your API key
        config('your-domain.com_identifier'),  // e.g., 'myapp-laravel'
        config('app.env') !== 'production'    // isTest - true for non-production
    )
    ->setEnabled(config('vitalytics.analytics_enabled', false));

// Track API calls (in middleware)
VitalyticsAnalytics::instance()->trackApiCall(
    $request->path(),
    $request->method(),
    $response->status(),
    $durationMs
);

// Track features
VitalyticsAnalytics::instance()->trackFeature('report_generated', [
    'type' => 'monthly',
    'format' => 'pdf'
]);

// Track background jobs
VitalyticsAnalytics::instance()->trackJob('ProcessInvoice', 'completed', [
    'invoice_id' => $invoice->id
]);
```

#### Laravel Page/Screen Tracking

For web applications, track page views using middleware:

```php
// app/Http/Middleware/TrackPageView.php
namespace App\Http\Middleware;

use App\Services\VitalyticsAnalytics;
use Closure;

class TrackPageView
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Only track successful page renders
        if ($response->isSuccessful() && $request->isMethod('GET')) {
            $routeName = $request->route()?->getName() ?? $request->path();

            VitalyticsAnalytics::instance()->trackScreen($routeName, [
                'path' => $request->path(),
                'referrer' => $request->header('Referer'),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $response;
    }
}
```

**Track user journeys in controllers:**

```php
class PatientController extends Controller
{
    public function show(Patient $patient)
    {
        // Track page view with context
        VitalyticsAnalytics::instance()->trackScreen('PatientDetail', [
            'patientId' => $patient->id,
        ]);

        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient)
    {
        VitalyticsAnalytics::instance()->trackScreen('PatientEdit', [
            'patientId' => $patient->id,
        ]);

        return view('patients.edit', compact('patient'));
    }
}
```

**Track form submissions and actions:**

```php
public function store(Request $request)
{
    // Track the action
    VitalyticsAnalytics::instance()->trackEvent('patient_created', 'form', [
        'source' => $request->input('source', 'web'),
    ]);

    // Create patient...
}

public function startRecording(Patient $patient)
{
    VitalyticsAnalytics::instance()->trackScreen('Recording');
    VitalyticsAnalytics::instance()->trackEvent('recording_started', 'feature', [
        'patientId' => $patient->id,
    ]);

    // Start recording logic...
}
```

**Complete server-side journey example:**

```php
// User views dashboard
VitalyticsAnalytics::instance()->trackScreen('Dashboard');

// User searches for patient
VitalyticsAnalytics::instance()->trackEvent('patient_search', 'interaction', [
    'query' => $searchQuery,
]);

// User views patient
VitalyticsAnalytics::instance()->trackScreen('PatientDetail', ['patientId' => $patient->id]);

// User generates report
VitalyticsAnalytics::instance()->trackScreen('ReportGenerator');
VitalyticsAnalytics::instance()->trackEvent('report_generated', 'feature', [
    'type' => 'soap_note',
    'patientId' => $patient->id,
]);
```

---

### .NET (Windows)

```csharp
using System.Net.Http.Json;
using System.Text.Json;
using System.Text.Json.Serialization;

namespace YourApp.Services
{
    public class VitalyticsAnalytics
    {
        private static VitalyticsAnalytics? _instance;
        private static readonly object _lock = new();

        public static VitalyticsAnalytics Instance
        {
            get
            {
                if (_instance == null)
                {
                    lock (_lock) { _instance ??= new VitalyticsAnalytics(); }
                }
                return _instance;
            }
        }

        private readonly HttpClient _httpClient;
        private readonly List<AnalyticsEvent> _eventQueue = new();
        private readonly object _queueLock = new();

        private string _baseUrl = "";
        private string _apiKey = "";
        private string _appIdentifier = "";
        private bool _enabled = false; // Disabled by default
        private bool _isTest = false;

        private string _sessionId = Guid.NewGuid().ToString();
        private string? _currentScreen;
        private DateTime? _screenStartTime;
        private Timer? _flushTimer;

        private VitalyticsAnalytics()
        {
            _httpClient = new HttpClient { Timeout = TimeSpan.FromSeconds(30) };
        }

        // Use the same config as health monitoring - no separate credentials needed
        public void Initialize(string baseUrl, string apiKey, string appIdentifier, bool isTest = false)
        {
            _baseUrl = baseUrl.TrimEnd('/');
            _apiKey = apiKey;
            _appIdentifier = appIdentifier;
            _isTest = isTest;

            _httpClient.DefaultRequestHeaders.Clear();
            _httpClient.DefaultRequestHeaders.Add("X-API-Key", _apiKey);
        }

        public bool IsEnabled => _enabled;

        public void SetEnabled(bool enabled)
        {
            _enabled = enabled;
            if (enabled)
            {
                StartSession();
            }
            else
            {
                StopSession();
                lock (_queueLock) { _eventQueue.Clear(); }
            }
        }

        public void TrackScreen(string screenName, Dictionary<string, object>? properties = null)
        {
            if (!_enabled) return;

            // Track duration on previous screen
            if (_currentScreen != null && _screenStartTime.HasValue)
            {
                var duration = (int)(DateTime.UtcNow - _screenStartTime.Value).TotalMilliseconds;
                QueueEvent("screen_duration", "navigation", _currentScreen, duration: duration);
            }

            _currentScreen = screenName;
            _screenStartTime = DateTime.UtcNow;

            QueueEvent("screen_viewed", "navigation", screenName, properties);
        }

        public void TrackEvent(string name, string category, Dictionary<string, object>? properties = null)
        {
            if (!_enabled) return;
            QueueEvent(name, category, _currentScreen, properties);
        }

        public void TrackClick(string elementId, Dictionary<string, object>? properties = null)
        {
            var props = properties ?? new Dictionary<string, object>();
            props["element"] = elementId;
            TrackEvent("button_clicked", "interaction", props);
        }

        public void TrackFeature(string featureName, Dictionary<string, object>? properties = null)
        {
            var props = properties ?? new Dictionary<string, object>();
            props["feature"] = featureName;
            TrackEvent("feature_used", "feature", props);
        }

        private void QueueEvent(string name, string category, string? screen = null,
            Dictionary<string, object>? properties = null, int? duration = null)
        {
            var evt = new AnalyticsEvent
            {
                Name = name,
                Category = category,
                Screen = screen,
                Properties = properties,
                Duration = duration
            };

            lock (_queueLock)
            {
                _eventQueue.Add(evt);
                if (_eventQueue.Count >= 20)
                {
                    _ = FlushAsync();
                }
            }
        }

        public async Task FlushAsync()
        {
            if (!_enabled) return;

            List<AnalyticsEvent> eventsToSend;
            lock (_queueLock)
            {
                if (_eventQueue.Count == 0) return;
                eventsToSend = new List<AnalyticsEvent>(_eventQueue);
                _eventQueue.Clear();
            }

            var batch = new AnalyticsBatch
            {
                AppIdentifier = _appIdentifier,
                SessionId = _sessionId,
                IsTest = _isTest,
                DeviceInfo = GetDeviceInfo(),
                Events = eventsToSend
            };

            try
            {
                var response = await _httpClient.PostAsJsonAsync(
                    $"{_baseUrl}/api/v1/analytics/events", batch);
                response.EnsureSuccessStatusCode();
            }
            catch
            {
                // Re-queue on failure
                lock (_queueLock)
                {
                    _eventQueue.InsertRange(0, eventsToSend);
                }
            }
        }

        private AnalyticsDeviceInfo GetDeviceInfo()
        {
            return new AnalyticsDeviceInfo
            {
                DeviceId = GetDeviceId(),
                DeviceModel = Environment.MachineName,
                Platform = "windows",
                OsVersion = Environment.OSVersion.ToString(),
                AppVersion = "1.0.0"
            };
        }

        private string GetDeviceId()
        {
            var path = Path.Combine(AppContext.BaseDirectory, ".vitalytics_analytics_device_id");
            if (File.Exists(path))
                return File.ReadAllText(path).Trim();

            var deviceId = $"dev-{Guid.NewGuid()}";
            File.WriteAllText(path, deviceId);
            return deviceId;
        }

        private void StartSession()
        {
            _sessionId = Guid.NewGuid().ToString();
            QueueEvent("session_started", "session");

            _flushTimer = new Timer(_ => _ = FlushAsync(), null,
                TimeSpan.FromSeconds(30), TimeSpan.FromSeconds(30));
        }

        private void StopSession()
        {
            _flushTimer?.Dispose();
            _flushTimer = null;
        }
    }

    // DTOs
    // IMPORTANT: All timestamps must be UTC (GMT+0)
    public class AnalyticsEvent
    {
        [JsonPropertyName("id")]
        public string Id { get; set; } = Guid.NewGuid().ToString();

        [JsonPropertyName("timestamp")]
        public string Timestamp { get; set; } = DateTime.UtcNow.ToString("o"); // Must use UtcNow!

        [JsonPropertyName("name")]
        public string Name { get; set; } = "";

        [JsonPropertyName("category")]
        public string Category { get; set; } = "";

        [JsonPropertyName("screen")]
        public string? Screen { get; set; }

        [JsonPropertyName("properties")]
        public Dictionary<string, object>? Properties { get; set; }

        [JsonPropertyName("duration")]
        public int? Duration { get; set; }
    }

    public class AnalyticsDeviceInfo
    {
        [JsonPropertyName("deviceId")]
        public string DeviceId { get; set; } = "";

        [JsonPropertyName("deviceModel")]
        public string? DeviceModel { get; set; }

        [JsonPropertyName("platform")]
        public string Platform { get; set; } = "windows";

        [JsonPropertyName("osVersion")]
        public string? OsVersion { get; set; }

        [JsonPropertyName("appVersion")]
        public string? AppVersion { get; set; }
    }

    public class AnalyticsBatch
    {
        [JsonPropertyName("batchId")]
        public string BatchId { get; set; } = Guid.NewGuid().ToString();

        [JsonPropertyName("appIdentifier")]
        public string AppIdentifier { get; set; } = "";

        [JsonPropertyName("deviceInfo")]
        public AnalyticsDeviceInfo DeviceInfo { get; set; } = new();

        [JsonPropertyName("sessionId")]
        public string SessionId { get; set; } = "";

        [JsonPropertyName("isTest")]
        public bool IsTest { get; set; }

        [JsonPropertyName("sentAt")]
        public string SentAt { get; set; } = DateTime.UtcNow.ToString("o");

        [JsonPropertyName("events")]
        public List<AnalyticsEvent> Events { get; set; } = new();
    }
}
```

#### Usage

```csharp
// Initialize on app startup - use the same config as health monitoring
VitalyticsAnalytics.Instance.Initialize(
    baseUrl: "https://vitalytics.yourserver.com",  // Same as health monitoring
    apiKey: "your-api-key",                        // Same API key as health
    appIdentifier: "myapp-windows",                // Same app identifier as health
    isTest: true                                   // Set false for production
);

// Enable after user consent
VitalyticsAnalytics.Instance.SetEnabled(true);

// Track screens
VitalyticsAnalytics.Instance.TrackScreen("MainWindow");
VitalyticsAnalytics.Instance.TrackScreen("SettingsDialog", new Dictionary<string, object> {
    { "source", "menu" }
});

// Track interactions
VitalyticsAnalytics.Instance.TrackClick("save-button");
VitalyticsAnalytics.Instance.TrackFeature("export", new Dictionary<string, object> {
    { "format", "pdf" }
});
```

#### Windows Forms Screen Tracking

**Track form open/activation:**

```csharp
public partial class PatientForm : Form
{
    protected override void OnShown(EventArgs e)
    {
        base.OnShown(e);
        VitalyticsAnalytics.Instance.TrackScreen("PatientForm");
    }
}

public partial class RecordingForm : Form
{
    private string _patientId;

    protected override void OnShown(EventArgs e)
    {
        base.OnShown(e);
        VitalyticsAnalytics.Instance.TrackScreen("RecordingForm", new Dictionary<string, object> {
            { "patientId", _patientId }
        });
    }
}
```

**Track dialogs:**

```csharp
private void ShowSettingsDialog()
{
    VitalyticsAnalytics.Instance.TrackScreen("SettingsDialog");
    using var dialog = new SettingsDialog();
    dialog.ShowDialog();
}
```

#### WPF Screen Tracking

**Track window loaded:**

```csharp
public partial class PatientWindow : Window
{
    public PatientWindow()
    {
        InitializeComponent();
        Loaded += OnLoaded;
    }

    private void OnLoaded(object sender, RoutedEventArgs e)
    {
        VitalyticsAnalytics.Instance.TrackScreen("PatientWindow");
    }
}
```

**Track navigation in NavigationWindow:**

```csharp
public partial class MainWindow : NavigationWindow
{
    public MainWindow()
    {
        InitializeComponent();
        Navigated += OnNavigated;
    }

    private void OnNavigated(object sender, NavigationEventArgs e)
    {
        var pageName = e.Content?.GetType().Name ?? "Unknown";
        VitalyticsAnalytics.Instance.TrackScreen(pageName);
    }
}
```

**Track UserControl views:**

```csharp
public partial class PatientDetailsView : UserControl
{
    public PatientDetailsView()
    {
        InitializeComponent();
        Loaded += OnLoaded;
    }

    private void OnLoaded(object sender, RoutedEventArgs e)
    {
        VitalyticsAnalytics.Instance.TrackScreen("PatientDetailsView");
    }
}
```

#### Complete Windows journey example:

```csharp
// User opens app - MainWindow.OnLoaded
VitalyticsAnalytics.Instance.TrackScreen("MainWindow");

// User opens patient - PatientForm.OnShown
VitalyticsAnalytics.Instance.TrackScreen("PatientForm", new Dictionary<string, object> {
    { "patientId", "123" }
});
VitalyticsAnalytics.Instance.TrackEvent("patient_loaded", "interaction");

// User selects template
VitalyticsAnalytics.Instance.TrackEvent("template_selected", "interaction", new Dictionary<string, object> {
    { "templateId", "soap-note" },
    { "templateName", "SOAP Note" }
});

// User starts recording - RecordingForm.OnShown
VitalyticsAnalytics.Instance.TrackScreen("RecordingForm");
VitalyticsAnalytics.Instance.TrackEvent("recording_started", "feature");

// User stops recording
VitalyticsAnalytics.Instance.TrackEvent("recording_stopped", "feature", new Dictionary<string, object> {
    { "durationSeconds", 125 }
});

// User reviews note - ReviewForm.OnShown
VitalyticsAnalytics.Instance.TrackScreen("ReviewForm");
VitalyticsAnalytics.Instance.TrackEvent("note_generated", "feature");
```

---

## Auto-Tracking

Auto-tracking captures events automatically without explicit code. Enable selectively based on your needs.

### Web/Chrome - Auto Page Views

```javascript
// Auto-track page views on route changes
window.addEventListener('popstate', () => {
    analytics.trackScreen(window.location.pathname);
});

// For SPAs with hash routing
window.addEventListener('hashchange', () => {
    analytics.trackScreen(window.location.hash);
});

// For React Router (example)
history.listen((location) => {
    analytics.trackScreen(location.pathname);
});
```

### Web - Auto Click Tracking

```javascript
// Track clicks on elements with data-track attribute
document.addEventListener('click', (e) => {
    const target = e.target.closest('[data-track]');
    if (target) {
        const trackId = target.dataset.track;
        const trackData = target.dataset.trackData
            ? JSON.parse(target.dataset.trackData)
            : {};
        analytics.trackClick(trackId, trackData);
    }
});
```

### iOS - Auto Screen Tracking

```swift
// In AppDelegate or SceneDelegate
extension UIViewController {
    @objc func viewDidAppearWithTracking(_ animated: Bool) {
        viewDidAppearWithTracking(animated)
        VitalyticsAnalytics.shared.trackScreen(String(describing: type(of: self)))
    }

    static func enableAutoTracking() {
        let originalSelector = #selector(UIViewController.viewDidAppear(_:))
        let swizzledSelector = #selector(UIViewController.viewDidAppearWithTracking(_:))

        guard let originalMethod = class_getInstanceMethod(UIViewController.self, originalSelector),
              let swizzledMethod = class_getInstanceMethod(UIViewController.self, swizzledSelector) else {
            return
        }

        method_exchangeImplementations(originalMethod, swizzledMethod)
    }
}
```

---

## Declarative Tracking

Use HTML data attributes for tracking without writing JavaScript:

```html
<!-- Track button clicks -->
<button data-track="signup-button" data-track-data='{"plan":"premium"}'>
    Sign Up
</button>

<!-- Track link clicks -->
<a href="/pricing" data-track="pricing-link">View Pricing</a>

<!-- Track form submissions -->
<form data-track-submit="contact-form">
    <!-- form fields -->
</form>

<!-- Track visibility (when element enters viewport) -->
<div data-track-view="hero-section" data-track-view-threshold="0.5">
    <!-- content -->
</div>
```

JavaScript handler:

```javascript
// Form submission tracking
document.querySelectorAll('[data-track-submit]').forEach(form => {
    form.addEventListener('submit', () => {
        analytics.trackEvent('form_submitted', 'form', {
            formName: form.dataset.trackSubmit
        });
    });
});

// Visibility tracking with Intersection Observer
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const element = entry.target;
            analytics.trackEvent('element_viewed', 'content', {
                element: element.dataset.trackView
            });
            observer.unobserve(element); // Track only once
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('[data-track-view]').forEach(el => observer.observe(el));
```

---

## Best Practices

### 1. Event Naming

- Use `snake_case` for event names
- Be descriptive but concise: `signup_completed`, `report_downloaded`
- Use past tense for completed actions: `button_clicked`, `form_submitted`
- Use present tense for ongoing states: `video_playing`, `loading_started`

### 2. Properties

- Limit to 10 key-value pairs per event
- Use consistent property names across events
- Avoid PII (names, emails, phone numbers)
- Include context: `source`, `variant`, `category`

### 3. Testing

- Always use `isTest: true` during development
- Verify events appear in dashboard before going to production
- Test offline behavior and retry logic
- Validate batch formatting with API directly

### 4. Performance

- Don't track every micro-interaction
- Use batching (don't send events one at a time)
- Flush on app background/close
- Set reasonable flush intervals (30 seconds default)

### 5. Session Management

- Generate new session ID on app launch
- Generate new session after 30 minutes of inactivity
- Track session start/end for engagement metrics

### 6. Timestamps

- **Always use UTC (GMT+0)** for all timestamps
- Use ISO 8601 format with `Z` suffix: `2026-01-09T14:30:00Z`
- JavaScript: `new Date().toISOString()` (automatically UTC)
- Swift: `ISO8601DateFormatter()` with `timeZone = UTC`
- Kotlin: `SimpleDateFormat` with `timeZone = UTC`
- PHP: `now()->utc()->toIso8601String()`
- .NET: `DateTime.UtcNow.ToString("o")`

---

## Privacy & Consent

### Opt-In Implementation

Analytics should be disabled by default. Enable only after user consent:

```javascript
// Check user preference
const analyticsConsent = localStorage.getItem('analytics_consent');

const analytics = new VitalyticsAnalytics({
    enabled: analyticsConsent === 'true',
    // ...
});

// When user grants consent
function enableAnalytics() {
    localStorage.setItem('analytics_consent', 'true');
    analytics.setEnabled(true);
}

// When user revokes consent
function disableAnalytics() {
    localStorage.setItem('analytics_consent', 'false');
    analytics.setEnabled(false);
}
```

### Data Minimization

- Use anonymous user IDs, not real user IDs
- Don't track PII in properties
- Don't track sensitive screens (login, payment details)
- Properties are limited to 10 keys to prevent over-collection

### User Rights

If users request data deletion:
1. Clear local device ID and anonymous ID
2. Generate new IDs on next session
3. Historical data cannot be linked to new IDs

---

## Support

For questions or issues with analytics integration:
- Check the Vitalytics dashboard help pages
- Review API responses for error details
- Verify API key permissions

---

*Last updated: January 2026*
