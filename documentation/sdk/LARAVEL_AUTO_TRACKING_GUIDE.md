# Vitalytics Auto-Tracking Implementation Guide

## Overview

This guide explains how to replace manual `trackClick()` calls with declarative HTML data attributes for cleaner, more maintainable analytics tracking.

---

## Current Implementation (To Be Replaced)

You currently have tracking code like this in Livewire methods:

```php
public function savePatient()
{
    try {
        VitalyticsAnalytics::instance()->trackClick('save-patient-button');
    } catch (\Exception $e) {
        // Silently fail
    }

    $this->patient->save();
}

public function closeEditModal()
{
    try {
        VitalyticsAnalytics::instance()->trackClick('close-edit-modal');
    } catch (\Exception $e) {
        // Silently fail
    }

    $this->showModal = false;
}
```

**Problems with this approach:**
- Repetitive boilerplate code
- Clutters business logic
- Easy to forget adding tracking
- Requires try/catch in every method
- Hard to audit what's being tracked

---

## New Implementation (Data Attributes)

### Step 1: Add the Tracking Script to Your Layout

Add this script tag before `</body>` in your main layout file (e.g., `resources/views/layouts/app.blade.php`):

```html
<!-- Vitalytics Auto-Tracking -->
<script>
(function() {
    'use strict';

    // Get CSRF token for Laravel
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // Track click events
    document.addEventListener('click', function(e) {
        const el = e.target.closest('[data-vitalytics-click]');
        if (!el) return;

        const data = {
            type: 'click',
            element: el.dataset.vitalyticsClick,
            screen: el.dataset.vitalyticsScreen || null,
            properties: el.dataset.vitalyticsProps ? JSON.parse(el.dataset.vitalyticsProps) : null
        };

        // Send tracking data
        fetch('/vitalytics/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        }).catch(function() {
            // Silently fail - don't break user experience
        });
    });

    // Track feature usage
    document.addEventListener('click', function(e) {
        const el = e.target.closest('[data-vitalytics-feature]');
        if (!el) return;

        const data = {
            type: 'feature',
            feature: el.dataset.vitalyticsFeature,
            screen: el.dataset.vitalyticsScreen || null,
            properties: el.dataset.vitalyticsProps ? JSON.parse(el.dataset.vitalyticsProps) : null
        };

        fetch('/vitalytics/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        }).catch(function() {});
    });

    // Auto-track screen views on elements that become visible
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    const screenEl = node.querySelector ?
                        (node.matches('[data-vitalytics-screen-view]') ? node : node.querySelector('[data-vitalytics-screen-view]'))
                        : null;
                    if (screenEl) {
                        fetch('/vitalytics/track', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                type: 'screen',
                                screen: screenEl.dataset.vitalyticsScreenView
                            })
                        }).catch(function() {});
                    }
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
})();
</script>
```

### Step 2: Add the Laravel Tracking Endpoint

Create a new route in `routes/web.php`:

```php
use Illuminate\Http\Request;
use Vitalytics\Facades\VitalyticsAnalytics;

Route::post('/vitalytics/track', function (Request $request) {
    $type = $request->input('type');
    $screen = $request->input('screen');
    $properties = $request->input('properties', []);

    // Set screen context if provided
    if ($screen) {
        VitalyticsAnalytics::setScreen($screen);
    }

    switch ($type) {
        case 'click':
            $element = $request->input('element');
            if ($element) {
                VitalyticsAnalytics::trackClick($element, $properties ?: []);
            }
            break;

        case 'feature':
            $feature = $request->input('feature');
            if ($feature) {
                VitalyticsAnalytics::trackFeature($feature, $properties ?: []);
            }
            break;

        case 'screen':
            $screenName = $request->input('screen');
            if ($screenName) {
                VitalyticsAnalytics::trackScreen($screenName, $properties ?: []);
            }
            break;
    }

    return response()->json(['ok' => true]);
})->middleware('web')->name('vitalytics.track');
```

### Step 3: Update Your Blade/Livewire Templates

Replace manual tracking with data attributes:

#### Button Clicks

**Before (in PHP):**
```php
public function savePatient()
{
    try {
        VitalyticsAnalytics::instance()->trackClick('save-patient-button');
    } catch (\Exception $e) {}

    $this->patient->save();
}
```

**After (in Blade):**
```html
<button
    data-vitalytics-click="save-patient-button"
    wire:click="savePatient">
    Save Patient
</button>
```

```php
// PHP is now clean!
public function savePatient()
{
    $this->patient->save();
}
```

#### With Screen Context (for Modals)

```html
<button
    data-vitalytics-click="save-patient-button"
    data-vitalytics-screen="EditPatientModal"
    wire:click="savePatient">
    Save Patient
</button>
```

#### With Additional Properties

```html
<button
    data-vitalytics-click="delete-patient-button"
    data-vitalytics-props='{"patient_id": {{ $patient->id }}}'
    wire:click="deletePatient({{ $patient->id }})">
    Delete
</button>
```

#### Feature Usage

```html
<button
    data-vitalytics-feature="export-pdf"
    data-vitalytics-props='{"report_type": "monthly"}'
    wire:click="exportPdf">
    Export PDF
</button>
```

#### Modal Screen Tracking

When a modal opens, track it as a screen view:

```html
<div
    x-show="showEditModal"
    x-transition
    data-vitalytics-screen-view="EditPatientModal">

    <!-- Modal content -->
    <button
        data-vitalytics-click="close-edit-modal"
        @click="showEditModal = false">
        Close
    </button>

    <button
        data-vitalytics-click="save-patient-modal-button"
        wire:click="savePatient">
        Save
    </button>
</div>
```

---

## Available Data Attributes

| Attribute | Purpose | Example |
|-----------|---------|---------|
| `data-vitalytics-click="name"` | Track button/link clicks | `data-vitalytics-click="save-btn"` |
| `data-vitalytics-feature="name"` | Track feature usage | `data-vitalytics-feature="export-pdf"` |
| `data-vitalytics-screen="name"` | Set screen context for the event | `data-vitalytics-screen="PatientModal"` |
| `data-vitalytics-screen-view="name"` | Track screen view when element appears | `data-vitalytics-screen-view="EditModal"` |
| `data-vitalytics-props='{"key":"value"}'` | Additional event properties (JSON) | `data-vitalytics-props='{"id":123}'` |

---

## Migration Checklist

For each Livewire component:

1. **Find all `trackClick()` calls** in PHP methods
2. **Add `data-vitalytics-click`** attribute to the corresponding button/link in Blade
3. **Remove the try/catch block** from the PHP method
4. **For modals**, add `data-vitalytics-screen` or `data-vitalytics-screen-view` attributes

### Example Migration

**Before:**
```php
// PatientList.php
public function openEditModal($patientId)
{
    try {
        VitalyticsAnalytics::instance()->trackClick('edit-patient-button');
    } catch (\Exception $e) {}

    $this->editingPatient = Patient::find($patientId);
    $this->showEditModal = true;
}

public function closeEditModal()
{
    try {
        VitalyticsAnalytics::instance()->trackClick('close-edit-modal');
    } catch (\Exception $e) {}

    $this->showEditModal = false;
}

public function savePatient()
{
    try {
        VitalyticsAnalytics::instance()->trackClick('save-patient-button');
    } catch (\Exception $e) {}

    $this->editingPatient->save();
    $this->showEditModal = false;
}
```

**After:**
```php
// PatientList.php - Clean!
public function openEditModal($patientId)
{
    $this->editingPatient = Patient::find($patientId);
    $this->showEditModal = true;
}

public function closeEditModal()
{
    $this->showEditModal = false;
}

public function savePatient()
{
    $this->editingPatient->save();
    $this->showEditModal = false;
}
```

```html
<!-- patient-list.blade.php -->
<button
    data-vitalytics-click="edit-patient-button"
    data-vitalytics-props='{"patient_id": {{ $patient->id }}}'
    wire:click="openEditModal({{ $patient->id }})">
    Edit
</button>

<!-- Modal -->
<div x-show="showEditModal" data-vitalytics-screen-view="EditPatientModal">
    <button
        data-vitalytics-click="close-edit-modal"
        wire:click="closeEditModal">
        Close
    </button>

    <button
        data-vitalytics-click="save-patient-button"
        wire:click="savePatient">
        Save
    </button>
</div>
```

---

## Server-Side Tracking (Still Available)

You can still use server-side tracking for events that don't have a UI element:

```php
// For API calls, background jobs, etc.
VitalyticsAnalytics::trackApiCall($path, $method, $status, $durationMs);
VitalyticsAnalytics::trackJob('ProcessInvoice', 'completed', [...]);
VitalyticsAnalytics::trackSearch($query, $resultCount);
VitalyticsAnalytics::trackForm('contact', 'submitted', [...]);
```

---

## Testing

After implementation:

1. Open browser DevTools → Network tab
2. Click a tracked element
3. Verify POST request to `/vitalytics/track`
4. Check Vitalytics dashboard for events appearing

---

## Questions?

- SDK Documentation: https://your-vitalytics-server.com/docs/analytics-sdk
- Current SDK Version: 1.0.10
