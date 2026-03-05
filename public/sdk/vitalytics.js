/**
 * Vitalytics Universal SDK v1.3.2
 *
 * Combined Health Monitoring + Analytics + Feedback for websites and Chrome extensions.
 * Automatically detects environment and adapts storage/behavior.
 *
 * Usage:
 *   <script src="https://your-vitalytics-server.com/sdk/vitalytics.js"></script>
 *   <script>
 *     Vitalytics.init({
 *       apiKey: 'your-api-key',
 *       appIdentifier: 'your-app-identifier',
 *       consentMode: 'standard-with-fallback'
 *     });
 *
 *     // When user makes consent choice:
 *     Vitalytics.setConsent(true);  // Standard Analytics (with device ID)
 *     Vitalytics.setConsent(false); // Privacy Mode (no device ID, but still tracks!)
 *   </script>
 *
 * Health Monitoring:
 *   - Automatic error capture (window.onerror, unhandledrejection)
 *   - Manual: crash(), error(), warning(), info()
 *   - Optional heartbeat monitoring
 *
 * Analytics Tracking:
 *   - Auto-tracks all button/link clicks
 *   - Auto-tracks all form submissions
 *   - Auto-tracks page views (including SPA navigation)
 *   - PHI-safe mode for HIPAA compliance
 *
 * User Feedback (NEW in v1.3.0):
 *   - submitFeedback(message, options) - Collect user feedback
 *   - Categories: general, bug, feature-request, praise
 *   - Optional: rating (1-5), email, metadata
 *
 * Collection Modes:
 *   - 'standard': Full analytics with device ID (cross-session tracking)
 *   - 'privacy': No device ID (session-only tracking)
 *   - 'standard-with-fallback': Consent for standard, fallback to privacy
 *
 * Works in Chrome extensions and regular websites.
 */
(function(global) {
    'use strict';

    // =========================================================================
    // Environment Detection
    // =========================================================================

    var isExtension = typeof chrome !== 'undefined'
                   && chrome.storage
                   && chrome.storage.local
                   && typeof chrome.runtime !== 'undefined';

    var platform = isExtension ? 'chrome-extension' : 'web';

    // =========================================================================
    // Storage Abstraction
    // =========================================================================

    var Storage = {
        get: function(key) {
            return new Promise(function(resolve) {
                if (isExtension) {
                    chrome.storage.local.get(key, function(result) {
                        resolve(result[key] || null);
                    });
                } else {
                    try {
                        resolve(localStorage.getItem(key));
                    } catch (e) {
                        resolve(null);
                    }
                }
            });
        },

        set: function(key, value) {
            return new Promise(function(resolve) {
                if (isExtension) {
                    var data = {};
                    data[key] = value;
                    chrome.storage.local.set(data, resolve);
                } else {
                    try {
                        localStorage.setItem(key, value);
                    } catch (e) {}
                    resolve();
                }
            });
        }
    };

    // =========================================================================
    // Utility Functions
    // =========================================================================

    function generateUUID() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    function getScreenFromUrl() {
        var path = window.location.pathname;
        var screen = path.replace(/^\//, '') || 'home';
        screen = screen.replace(/\/\d+\//g, '-').replace(/\/\d+$/, '');
        screen = screen.replace(/\//g, '-');
        return screen;
    }

    function log() {
        if (Vitalytics._debug && console && console.log) {
            var args = ['[Vitalytics]'].concat(Array.prototype.slice.call(arguments));
            console.log.apply(console, args);
        }
    }

    // =========================================================================
    // Main SDK
    // =========================================================================

    var Vitalytics = {
        _initialized: false,
        _enabled: false,
        _debug: false,
        _phiSafe: false,
        _config: {},

        // Analytics state
        _analyticsQueue: [],
        _sessionId: null,
        _currentScreen: null,
        _currentScreenLabel: null,
        _analyticsFlushTimer: null,

        // Health state
        _healthQueue: [],
        _healthFlushTimer: null,
        _heartbeatTimer: null,

        // Shared state
        _deviceId: null,
        _deviceIdPromise: null,

        // Consent state (NEW in v1.2.0)
        _collectionMode: 'privacy',      // 'standard' or 'privacy'
        _consentMode: 'none',            // 'none' or 'standard-with-fallback'
        _consentGiven: null,             // null = not decided, true = standard, false = privacy

        // =====================================================================
        // Initialization
        // =====================================================================

        /**
         * Initialize the SDK
         * @param {Object} config Configuration object
         * @param {string} config.apiKey - Your Vitalytics API key (required)
         * @param {string} config.appIdentifier - Your app identifier (required)
         * @param {string} [config.baseUrl] - API base URL (default: https://your-vitalytics-server.com)
         * @param {boolean} [config.enabled=true] - Enable tracking
         * @param {boolean} [config.isTest=false] - Mark events as test data
         * @param {string} [config.environment='production'] - Environment name
         * @param {boolean} [config.phiSafe=false] - Enable PHI-safe mode
         * @param {boolean} [config.autoTrack=true] - Enable analytics auto-tracking
         * @param {boolean} [config.captureErrors=true] - Auto-capture JS errors
         * @param {number} [config.heartbeatInterval=0] - Heartbeat interval in ms (0=disabled)
         * @param {boolean} [config.debug=false] - Enable debug logging
         * @param {number} [config.batchSize=20] - Events per batch
         * @param {number} [config.flushInterval=30000] - Flush interval in ms
         * @param {string} [config.appVersion] - App version string
         * @param {string} [config.userId] - Current user ID
         * @param {string} [config.collectionMode='privacy'] - Direct mode: 'standard' or 'privacy'
         * @param {string} [config.consentMode='none'] - Consent handling: 'none' or 'standard-with-fallback'
         */
        init: function(config) {
            if (this._initialized) {
                log('Already initialized');
                return this;
            }

            if (!config.apiKey || !config.appIdentifier) {
                console.error('[Vitalytics] apiKey and appIdentifier are required');
                return this;
            }

            this._config = {
                apiKey: config.apiKey,
                appIdentifier: config.appIdentifier,
                baseUrl: (config.baseUrl || 'https://your-vitalytics-server.com').replace(/\/$/, ''),
                isTest: config.isTest || false,
                environment: config.environment || 'production',
                batchSize: config.batchSize || 20,
                flushInterval: config.flushInterval || 30000,
                heartbeatInterval: config.heartbeatInterval || 0,
                appVersion: config.appVersion || '1.0.0',
                userId: config.userId || null
            };

            this._enabled = config.enabled !== false;
            this._debug = config.debug || false;
            this._phiSafe = config.phiSafe || false;
            this._sessionId = generateUUID();
            this._currentScreen = getScreenFromUrl();

            // Initialize consent/collection mode (NEW in v1.2.0)
            this._consentMode = config.consentMode || 'none';
            this._collectionMode = config.collectionMode || 'privacy';
            this._consentGiven = null;

            // Check for stored consent preference
            var self = this;
            Storage.get('vitalytics_consent').then(function(storedConsent) {
                if (storedConsent !== null && self._consentMode === 'standard-with-fallback') {
                    self._consentGiven = storedConsent === 'true';
                    log('Restored consent preference:', self._consentGiven ? 'standard' : 'privacy');
                }
            });

            // Check for PHI-safe meta tag
            var phiMeta = document.querySelector('meta[name="vitalytics-phi-safe"]');
            if (phiMeta && (phiMeta.content === 'true' || phiMeta.content === '1')) {
                this._phiSafe = true;
            }

            // Initialize device ID (async)
            this._initDeviceId();

            if (this._enabled) {
                // Start flush timers
                this._startAnalyticsFlushTimer();
                this._startHealthFlushTimer();

                // Setup auto error capture
                if (config.captureErrors !== false) {
                    this._setupErrorCapture();
                }

                // Setup analytics auto-tracking
                if (config.autoTrack !== false) {
                    this._setupAutoTracking();
                    this._trackPageView();
                }

                // Setup heartbeat if configured
                if (this._config.heartbeatInterval > 0) {
                    this._startHeartbeat();
                }
            }

            // Flush on page unload
            this._setupUnloadHandler();

            this._initialized = true;
            var modeInfo = this._consentMode === 'standard-with-fallback' ? '(consent-with-fallback)' : '(' + this._collectionMode + ')';
            log('Initialized', platform, this._phiSafe ? '(PHI-safe)' : '', modeInfo, 'v1.3.2');

            return this;
        },

        /**
         * Initialize device ID from storage or create new one
         */
        _initDeviceId: function() {
            var self = this;
            this._deviceIdPromise = Storage.get('vitalytics_device_id').then(function(id) {
                if (id) {
                    self._deviceId = id;
                } else {
                    self._deviceId = 'dev-' + generateUUID();
                    Storage.set('vitalytics_device_id', self._deviceId);
                }
                return self._deviceId;
            });
        },

        /**
         * Get device ID (async)
         */
        _getDeviceId: function() {
            if (this._deviceId) {
                return Promise.resolve(this._deviceId);
            }
            return this._deviceIdPromise;
        },

        /**
         * Get device info object
         * In Privacy Mode, deviceId is null to prevent cross-session tracking
         */
        _getDeviceInfo: function(deviceId) {
            // Privacy Mode: no device ID
            var effectiveDeviceId = this.isPrivacyMode() ? null : deviceId;

            return {
                deviceId: effectiveDeviceId,
                platform: platform,
                deviceModel: navigator.userAgent.substring(0, 100),
                osVersion: navigator.platform || 'unknown',
                appVersion: this._config.appVersion,
                language: navigator.language || 'en'
            };
        },

        // =====================================================================
        // Configuration
        // =====================================================================

        /**
         * Enable or disable tracking
         */
        setEnabled: function(enabled) {
            this._enabled = enabled;
            log('Tracking', enabled ? 'enabled' : 'disabled');
            return this;
        },

        /**
         * Set current user ID
         */
        setUserId: function(userId) {
            this._config.userId = userId;
            log('User ID set:', userId);
            return this;
        },

        /**
         * Enable PHI-safe mode
         */
        enablePHISafe: function() {
            this._phiSafe = true;
            log('PHI-safe mode enabled');
            return this;
        },

        /**
         * Enable debug logging
         */
        enableDebug: function() {
            this._debug = true;
            console.log('[Vitalytics] Debug enabled. Platform:', platform, 'PHI-safe:', this._phiSafe, 'Mode:', this.getEffectiveCollectionMode());
            return this;
        },

        /**
         * Check if running in Chrome extension
         */
        isExtension: function() {
            return isExtension;
        },

        /**
         * Get current platform
         */
        getPlatform: function() {
            return platform;
        },

        // =====================================================================
        // Consent Management (NEW in v1.2.0)
        // =====================================================================

        /**
         * Set user consent for Standard Analytics
         * In 'standard-with-fallback' mode:
         *   - true = Standard Analytics (with device ID)
         *   - false = Privacy Mode (no device ID, but still tracks!)
         *
         * @param {boolean} userAccepted - Whether user accepted Standard Analytics
         * @returns {Vitalytics} this for chaining
         */
        setConsent: function(userAccepted) {
            this._consentGiven = !!userAccepted;

            // Persist consent choice
            Storage.set('vitalytics_consent', String(this._consentGiven));

            log('Consent set:', userAccepted ? 'Standard Analytics' : 'Privacy Mode');
            return this;
        },

        /**
         * Check if user has made a consent choice
         * @returns {boolean}
         */
        hasConsentChoice: function() {
            return this._consentGiven !== null;
        },

        /**
         * Get the effective collection mode (considers consent state)
         * @returns {string} 'standard' or 'privacy'
         */
        getEffectiveCollectionMode: function() {
            if (this._consentMode === 'standard-with-fallback') {
                // Consent flow: standard if consented, privacy otherwise
                return this._consentGiven === true ? 'standard' : 'privacy';
            }
            // Direct mode selection
            return this._collectionMode;
        },

        /**
         * Check if currently in Privacy Mode (no device ID)
         * @returns {boolean}
         */
        isPrivacyMode: function() {
            return this.getEffectiveCollectionMode() === 'privacy';
        },

        /**
         * Check if currently in Standard Analytics mode (with device ID)
         * @returns {boolean}
         */
        isStandardMode: function() {
            return this.getEffectiveCollectionMode() === 'standard';
        },

        /**
         * Set collection mode directly (when not using consent flow)
         * @param {string} mode - 'standard' or 'privacy'
         * @returns {Vitalytics} this for chaining
         */
        setCollectionMode: function(mode) {
            if (mode !== 'standard' && mode !== 'privacy') {
                console.error('[Vitalytics] Invalid collection mode. Use "standard" or "privacy"');
                return this;
            }
            this._collectionMode = mode;
            log('Collection mode set:', mode);
            return this;
        },

        // =====================================================================
        // HEALTH MONITORING
        // =====================================================================

        /**
         * Report a crash (fatal error)
         */
        crash: function(message, context) {
            return this._queueHealthEvent('crash', message, context);
        },

        /**
         * Report an error
         */
        error: function(message, context) {
            return this._queueHealthEvent('error', message, context);
        },

        /**
         * Report a warning
         */
        warning: function(message, context) {
            return this._queueHealthEvent('warning', message, context);
        },

        /**
         * Report info
         */
        info: function(message, context) {
            return this._queueHealthEvent('info', message, context);
        },

        /**
         * Send a heartbeat
         */
        heartbeat: function(context) {
            return this._queueHealthEvent('heartbeat', 'heartbeat', context);
        },

        /**
         * Queue a health event
         */
        _queueHealthEvent: function(level, message, context) {
            if (!this._enabled) return this;

            var event = {
                id: generateUUID(),
                timestamp: new Date().toISOString(),
                level: level,
                message: message || '',
                metadata: context || {}
            };

            // Add stack trace for crashes and errors
            if ((level === 'crash' || level === 'error') && context && context.stack) {
                event.stackTrace = this._parseStackTrace(context.stack);
                delete event.metadata.stack;
            }

            // Add screen context
            if (this._currentScreen) {
                event.metadata.screen = this._currentScreen;
            }

            this._healthQueue.push(event);
            log('Health:', level, message);

            // Flush immediately for crashes
            if (level === 'crash') {
                this.flushHealth();
            } else if (this._healthQueue.length >= this._config.batchSize) {
                this.flushHealth();
            }

            return this;
        },

        /**
         * Parse stack trace into array format
         */
        _parseStackTrace: function(stack) {
            if (!stack) return [];
            if (Array.isArray(stack)) return stack;

            return stack.split('\n').slice(0, 20).map(function(line) {
                return { frame: line.trim() };
            });
        },

        /**
         * Setup automatic error capture
         */
        _setupErrorCapture: function() {
            var self = this;

            // Global error handler
            var originalOnError = window.onerror;
            window.onerror = function(message, source, lineno, colno, error) {
                self.crash(message, {
                    source: source,
                    line: lineno,
                    column: colno,
                    stack: error ? error.stack : null
                });

                if (originalOnError) {
                    return originalOnError.apply(this, arguments);
                }
                return false;
            };

            // Unhandled promise rejections
            window.addEventListener('unhandledrejection', function(event) {
                var reason = event.reason;
                var message = 'Unhandled Promise Rejection';
                var context = {};

                if (reason instanceof Error) {
                    message = reason.message || message;
                    context.stack = reason.stack;
                    context.name = reason.name;
                } else if (typeof reason === 'string') {
                    message = reason;
                } else if (reason) {
                    context.reason = String(reason);
                }

                self.error(message, context);
            });

            // Chrome extension specific errors
            if (isExtension && chrome.runtime && chrome.runtime.lastError) {
                // Check for runtime errors periodically
                var checkRuntimeError = function() {
                    if (chrome.runtime.lastError) {
                        self.error('Chrome Runtime Error', {
                            message: chrome.runtime.lastError.message
                        });
                    }
                };
                setInterval(checkRuntimeError, 5000);
            }

            log('Error capture enabled');
        },

        /**
         * Start heartbeat timer
         */
        _startHeartbeat: function() {
            var self = this;
            this._heartbeatTimer = setInterval(function() {
                self.heartbeat();
            }, this._config.heartbeatInterval);
            log('Heartbeat started:', this._config.heartbeatInterval, 'ms');
        },

        /**
         * Stop heartbeat timer
         */
        stopHeartbeat: function() {
            if (this._heartbeatTimer) {
                clearInterval(this._heartbeatTimer);
                this._heartbeatTimer = null;
                log('Heartbeat stopped');
            }
            return this;
        },

        /**
         * Start health flush timer
         */
        _startHealthFlushTimer: function() {
            var self = this;
            this._healthFlushTimer = setInterval(function() {
                self.flushHealth();
            }, this._config.flushInterval);
        },

        /**
         * Flush health events to server
         */
        flushHealth: function() {
            if (!this._enabled || this._healthQueue.length === 0) {
                return Promise.resolve(true);
            }

            var self = this;
            var events = this._healthQueue.slice();
            this._healthQueue = [];

            return this._getDeviceId().then(function(deviceId) {
                var batch = {
                    batchId: generateUUID(),
                    appIdentifier: self._config.appIdentifier,
                    environment: self._config.environment,
                    deviceInfo: self._getDeviceInfo(deviceId),
                    userId: self._config.userId,
                    isTest: self._config.isTest,
                    sentAt: new Date().toISOString(),
                    events: events
                };

                return fetch(self._config.baseUrl + '/api/v1/health/events', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': self._config.apiKey,
                        'X-App-Identifier': self._config.appIdentifier,
                        'X-SDK-Version': 'js-1.3.2'
                    },
                    body: JSON.stringify(batch)
                }).then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    log('Health flushed:', events.length, 'events');
                    return true;
                }).catch(function(error) {
                    log('Health flush failed:', error.message);
                    self._healthQueue = events.concat(self._healthQueue);
                    return false;
                });
            });
        },

        // =====================================================================
        // ANALYTICS TRACKING
        // =====================================================================

        /**
         * Track a screen/page view
         */
        trackScreen: function(screenName, properties) {
            if (!this._enabled) return this;

            this._currentScreen = screenName;
            if (properties && properties.screen_label) {
                this._currentScreenLabel = properties.screen_label;
            }

            this._queueAnalyticsEvent({
                eventType: 'screen_viewed',
                category: 'navigation',
                screen: screenName,
                properties: properties
            });

            log('Screen:', screenName);
            return this;
        },

        /**
         * Set current screen without tracking
         */
        setScreen: function(screenName, screenLabel) {
            this._currentScreen = screenName;
            this._currentScreenLabel = screenLabel || null;
            return this;
        },

        /**
         * Track a button/element click
         */
        trackClick: function(elementId, properties) {
            if (!this._enabled) return this;

            this._queueAnalyticsEvent({
                eventType: 'button_clicked',
                category: 'interaction',
                screen: this._currentScreen,
                element: elementId,
                properties: properties
            });

            log('Click:', elementId);
            return this;
        },

        /**
         * Track feature usage
         */
        trackFeature: function(featureName, properties) {
            if (!this._enabled) return this;

            var props = Object.assign({ feature: featureName }, properties || {});

            this._queueAnalyticsEvent({
                eventType: 'feature_used',
                category: 'feature',
                screen: this._currentScreen,
                properties: props
            });

            log('Feature:', featureName);
            return this;
        },

        /**
         * Track a form submission
         */
        trackForm: function(formName, properties) {
            if (!this._enabled) return this;

            var props = Object.assign({ form: formName }, properties || {});

            this._queueAnalyticsEvent({
                eventType: 'form_submitted',
                category: 'form',
                screen: this._currentScreen,
                properties: props
            });

            log('Form:', formName);
            return this;
        },

        /**
         * Track a custom event
         */
        trackEvent: function(eventType, category, properties) {
            if (!this._enabled) return this;

            this._queueAnalyticsEvent({
                eventType: eventType,
                category: category || 'custom',
                screen: this._currentScreen,
                properties: properties
            });

            log('Event:', eventType, category);
            return this;
        },

        /**
         * Track session start
         */
        trackSessionStart: function() {
            return this.trackEvent('session_started', 'session');
        },

        /**
         * Track session end and flush
         */
        trackSessionEnd: function() {
            this.trackEvent('session_ended', 'session');
            return this.flush();
        },

        /**
         * Queue an analytics event
         */
        _queueAnalyticsEvent: function(event) {
            var cleanEvent = {
                id: generateUUID(),
                timestamp: new Date().toISOString(),
                eventType: event.eventType,
                sessionId: this._sessionId,
                category: event.category
            };

            if (event.screen) cleanEvent.screen = event.screen;
            if (event.element) cleanEvent.element = event.element;
            if (event.properties && Object.keys(event.properties).length > 0) {
                cleanEvent.properties = event.properties;
            }

            this._analyticsQueue.push(cleanEvent);

            if (this._analyticsQueue.length >= this._config.batchSize) {
                this.flushAnalytics();
            }
        },

        /**
         * Start analytics flush timer
         */
        _startAnalyticsFlushTimer: function() {
            var self = this;
            this._analyticsFlushTimer = setInterval(function() {
                self.flushAnalytics();
            }, this._config.flushInterval);
        },

        /**
         * Flush analytics events to server
         */
        flushAnalytics: function() {
            if (!this._enabled || this._analyticsQueue.length === 0) {
                return Promise.resolve(true);
            }

            var self = this;
            var events = this._analyticsQueue.slice();
            this._analyticsQueue = [];

            return this._getDeviceId().then(function(deviceId) {
                var batch = {
                    batchId: generateUUID(),
                    appIdentifier: self._config.appIdentifier,
                    deviceInfo: self._getDeviceInfo(deviceId),
                    isTest: self._config.isTest,
                    sentAt: new Date().toISOString(),
                    events: events
                };

                return fetch(self._config.baseUrl + '/api/v1/analytics/events', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': self._config.apiKey,
                        'X-SDK-Version': 'js-1.3.2'
                    },
                    body: JSON.stringify(batch)
                }).then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    log('Analytics flushed:', events.length, 'events');
                    return true;
                }).catch(function(error) {
                    log('Analytics flush failed:', error.message);
                    self._analyticsQueue = events.concat(self._analyticsQueue);
                    return false;
                });
            });
        },

        /**
         * Flush both health and analytics
         */
        flush: function() {
            return Promise.all([
                this.flushHealth(),
                this.flushAnalytics()
            ]);
        },

        // =====================================================================
        // Auto-Tracking (Analytics)
        // =====================================================================

        /**
         * Setup automatic event tracking
         */
        _setupAutoTracking: function() {
            var self = this;

            // Track clicks
            document.addEventListener('click', function(e) {
                self._handleClick(e);
            }, true);

            // Track form submissions
            document.addEventListener('submit', function(e) {
                self._handleFormSubmit(e);
            }, true);

            // Track SPA navigation
            this._setupNavigationTracking();

            // Observe DOM for dynamic screen views
            this._setupMutationObserver();

            log('Auto-tracking enabled');
        },

        /**
         * Handle click events
         */
        _handleClick: function(e) {
            if (!this._enabled) return;

            var el = e.target;

            while (el && el !== document.body) {
                if (this._isInteractive(el)) break;
                el = el.parentElement;
            }

            if (!el || el === document.body) return;
            if (!this._shouldTrack(el)) return;

            if (el.dataset.vitalyticsFeature) {
                this._queueAnalyticsEvent({
                    eventType: 'feature_used',
                    category: 'feature',
                    screen: el.dataset.vitalyticsScreen || this._currentScreen,
                    properties: this._buildProperties(el, {
                        feature: el.dataset.vitalyticsFeature
                    })
                });
                return;
            }

            var identifier = this._getElementId(el);
            var properties = this._buildProperties(el);

            if (el.tagName.toLowerCase() === 'a' && el.href) {
                try {
                    var url = new URL(el.href);
                    properties.href = this._phiSafe ? url.pathname : el.href;
                    if (url.host !== window.location.host) {
                        properties.external = true;
                    }
                } catch (err) {}
            }

            this._queueAnalyticsEvent({
                eventType: 'button_clicked',
                category: 'interaction',
                screen: el.dataset.vitalyticsScreen || this._currentScreen,
                element: identifier,
                properties: Object.keys(properties).length > 0 ? properties : undefined
            });
        },

        /**
         * Handle form submissions
         */
        _handleFormSubmit: function(e) {
            if (!this._enabled) return;

            var form = e.target;
            if (!form || form.tagName.toLowerCase() !== 'form') return;
            if (!this._shouldTrack(form)) return;

            var identifier = this._getFormId(form);
            var properties = this._buildProperties(form, {
                form: identifier,
                method: form.method || 'get',
                fieldCount: form.querySelectorAll('input, select, textarea').length
            });

            this._queueAnalyticsEvent({
                eventType: 'form_submitted',
                category: 'form',
                screen: form.dataset.vitalyticsScreen || this._currentScreen,
                properties: properties
            });
        },

        /**
         * Track page view
         */
        _trackPageView: function() {
            this._currentScreen = getScreenFromUrl();

            var properties = {
                url: this._phiSafe ? window.location.pathname : window.location.href
            };

            if (!this._phiSafe && document.referrer) {
                properties.referrer = document.referrer;
            }

            var screenLabel = null;
            if (!this._phiSafe && document.title) {
                screenLabel = document.title;
            }

            this._queueAnalyticsEvent({
                eventType: 'screen_viewed',
                category: 'navigation',
                screen: this._currentScreen,
                properties: Object.assign(properties, screenLabel ? { screen_label: screenLabel } : {})
            });
        },

        /**
         * Setup navigation tracking for SPAs
         */
        _setupNavigationTracking: function() {
            var self = this;
            var lastUrl = window.location.href;

            function checkUrlChange() {
                if (window.location.href !== lastUrl) {
                    lastUrl = window.location.href;
                    self._trackPageView();
                }
            }

            var pushState = history.pushState;
            history.pushState = function() {
                pushState.apply(this, arguments);
                setTimeout(checkUrlChange, 0);
            };

            var replaceState = history.replaceState;
            history.replaceState = function() {
                replaceState.apply(this, arguments);
                setTimeout(checkUrlChange, 0);
            };

            window.addEventListener('popstate', function() {
                setTimeout(checkUrlChange, 0);
            });

            document.addEventListener('livewire:navigated', function() {
                setTimeout(function() { self._trackPageView(); }, 0);
            });

            document.addEventListener('turbo:load', function() {
                setTimeout(function() { self._trackPageView(); }, 0);
            });
        },

        /**
         * Setup mutation observer for dynamic content
         */
        _setupMutationObserver: function() {
            var self = this;
            var trackedViews = new Set();

            function trackScreenView(el) {
                var screenName = el.dataset.vitalyticsScreenView;
                var trackId = screenName + '-' + (el.id || Math.random().toString(36).substr(2, 9));

                if (trackedViews.has(trackId)) return;
                trackedViews.add(trackId);

                setTimeout(function() {
                    trackedViews.delete(trackId);
                }, 5000);

                self._queueAnalyticsEvent({
                    eventType: 'screen_viewed',
                    category: 'navigation',
                    screen: screenName,
                    properties: self._buildProperties(el)
                });
            }

            function checkNode(node) {
                if (node.nodeType !== 1) return;

                if (node.matches && node.matches('[data-vitalytics-screen-view]')) {
                    var style = window.getComputedStyle(node);
                    if (style.display !== 'none' && style.visibility !== 'hidden') {
                        trackScreenView(node);
                    }
                }

                var children = node.querySelectorAll ? node.querySelectorAll('[data-vitalytics-screen-view]') : [];
                children.forEach(function(child) {
                    var style = window.getComputedStyle(child);
                    if (style.display !== 'none' && style.visibility !== 'hidden') {
                        trackScreenView(child);
                    }
                });
            }

            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(checkNode);
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            document.querySelectorAll('[data-vitalytics-screen-view]').forEach(function(el) {
                var style = window.getComputedStyle(el);
                if (style.display !== 'none' && style.visibility !== 'hidden') {
                    trackScreenView(el);
                }
            });
        },

        /**
         * Setup unload handler
         */
        _setupUnloadHandler: function() {
            var self = this;

            window.addEventListener('beforeunload', function() {
                self.trackEvent('session_ended', 'session');

                if (navigator.sendBeacon) {
                    self._getDeviceId().then(function(deviceId) {
                        // Flush analytics
                        if (self._analyticsQueue.length > 0) {
                            var analyticsBatch = {
                                batchId: generateUUID(),
                                appIdentifier: self._config.appIdentifier,
                                deviceInfo: self._getDeviceInfo(deviceId),
                                isTest: self._config.isTest,
                                sentAt: new Date().toISOString(),
                                events: self._analyticsQueue
                            };
                            navigator.sendBeacon(
                                self._config.baseUrl + '/api/v1/analytics/events?apiKey=' + self._config.apiKey,
                                JSON.stringify(analyticsBatch)
                            );
                        }

                        // Flush health
                        if (self._healthQueue.length > 0) {
                            var healthBatch = {
                                batchId: generateUUID(),
                                appIdentifier: self._config.appIdentifier,
                                environment: self._config.environment,
                                deviceInfo: self._getDeviceInfo(deviceId),
                                userId: self._config.userId,
                                isTest: self._config.isTest,
                                sentAt: new Date().toISOString(),
                                events: self._healthQueue
                            };
                            navigator.sendBeacon(
                                self._config.baseUrl + '/api/v1/health/events?apiKey=' + self._config.apiKey,
                                JSON.stringify(healthBatch)
                            );
                        }
                    });
                }
            });

            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'hidden') {
                    self.flush();
                }
            });
        },

        // =====================================================================
        // Helper Methods
        // =====================================================================

        _isInteractive: function(el) {
            var tag = el.tagName.toLowerCase();
            if (tag === 'button') return true;
            if (tag === 'input' && ['button', 'submit', 'reset'].indexOf(el.type) !== -1) return true;
            if (tag === 'a' && el.href) return true;
            if (el.getAttribute('role') === 'button') return true;
            if (el.getAttribute('onclick')) return true;
            if (el.getAttribute('wire:click')) return true;
            if (el.getAttribute('x-on:click') || el.getAttribute('@click')) return true;
            if (el.getAttribute('v-on:click')) return true;
            try {
                if (window.getComputedStyle(el).cursor === 'pointer') return true;
            } catch (e) {}
            return false;
        },

        _shouldTrack: function(el) {
            if (el.dataset.vitalyticsIgnore !== undefined) return false;
            if (el.offsetParent === null && el.tagName !== 'BODY') return false;
            return true;
        },

        _getElementId: function(el) {
            if (el.dataset.vitalyticsClick) return el.dataset.vitalyticsClick;
            if (el.id) return el.id;
            if (el.name) return el.name;
            if (!this._phiSafe) {
                var text = (el.textContent || el.innerText || '').trim();
                if (text && text.length <= 50) {
                    return text.toLowerCase()
                        .replace(/[^a-z0-9\s]/g, '')
                        .replace(/\s+/g, '-')
                        .substring(0, 30);
                }
            }
            var classes = el.className;
            if (typeof classes === 'string' && classes) {
                var meaningful = classes.split(' ').find(function(c) {
                    return c.length > 2 && !/^(p-|m-|w-|h-|text-|bg-|flex|grid|block|inline|hidden)/.test(c);
                });
                if (meaningful) return meaningful;
            }
            return el.tagName.toLowerCase();
        },

        _getFormId: function(form) {
            if (form.dataset.vitalyticsForm) return form.dataset.vitalyticsForm;
            if (form.id) return form.id;
            if (form.name) return form.name;
            if (form.action) {
                try {
                    var url = new URL(form.action, window.location.origin);
                    return 'form-' + url.pathname.replace(/^\//, '').replace(/\//g, '-');
                } catch (e) {}
            }
            return 'form';
        },

        _buildProperties: function(el, extra) {
            var props = extra || {};
            var label = el.dataset.vitalyticsLabel;
            if (!label && !this._phiSafe) {
                var text = (el.textContent || el.innerText || '').trim();
                if (text && text.length <= 50) {
                    label = text;
                }
            }
            if (label) props.label = label;
            var screenLabel = el.dataset.vitalyticsScreenLabel || this._currentScreenLabel;
            if (screenLabel) props.screen_label = screenLabel;
            if (el.dataset.vitalyticsProps) {
                try {
                    var custom = JSON.parse(el.dataset.vitalyticsProps);
                    props = Object.assign(props, custom);
                } catch (e) {}
            }
            return props;
        },

        // =====================================================================
        // User Feedback
        // =====================================================================

        /**
         * Submit user feedback
         * @param {string} message - The feedback message (required)
         * @param {Object} options - Optional settings
         * @param {string} options.category - 'general', 'bug', 'feature-request', 'praise' (default: 'general')
         * @param {number} options.rating - 1-5 star rating
         * @param {string} options.email - User's email for follow-up
         * @param {Object} options.metadata - Additional custom data
         * @returns {Promise} Resolves when feedback is submitted
         */
        submitFeedback: function(message, options) {
            var self = this;
            options = options || {};

            if (!this._initialized) {
                log('SDK not initialized');
                return Promise.reject(new Error('SDK not initialized'));
            }

            if (!message || typeof message !== 'string' || message.trim().length === 0) {
                log('Feedback message is required');
                return Promise.reject(new Error('Feedback message is required'));
            }

            var validCategories = ['general', 'bug', 'feature-request', 'praise'];
            var category = options.category || 'general';
            if (validCategories.indexOf(category) === -1) {
                category = 'general';
            }

            var rating = options.rating !== undefined ? parseInt(options.rating, 10) : undefined;
            if (rating !== undefined && (isNaN(rating) || rating < 1 || rating > 5)) {
                rating = undefined;
            }

            var payload = {
                appIdentifier: this._config.appIdentifier,
                message: message.trim(),
                category: category,
                rating: rating,
                email: options.email || null,
                userId: options.userId || null,
                deviceId: this._getEffectiveDeviceId(),
                sessionId: this._sessionId,
                screen: this._currentScreen || null,
                deviceInfo: {
                    platform: platform,
                    osVersion: navigator.userAgent,
                    appVersion: this._config.appVersion || null
                },
                metadata: options.metadata || null,
                isTest: this._config.isTest || false
            };

            log('Submitting feedback:', category, rating ? '(' + rating + ' stars)' : '');

            return fetch(this._config.baseUrl + '/api/v1/feedback', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': this._config.apiKey,
                    'X-App-Identifier': this._config.appIdentifier,
                    'X-SDK-Version': 'js-1.3.2'
                },
                body: JSON.stringify(payload)
            }).then(function(response) {
                return response.json().then(function(data) {
                    if (!response.ok) {
                        log('Feedback error:', response.status, data);
                        var error = new Error(data.message || 'HTTP ' + response.status);
                        error.response = data;
                        throw error;
                    }
                    return data;
                });
            }).then(function(data) {
                log('Feedback submitted successfully:', data.feedbackId);
                return { success: true, feedbackId: data.feedbackId };
            }).catch(function(error) {
                log('Failed to submit feedback:', error.message);
                return { success: false, error: error.message, details: error.response };
            });
        },

        /**
         * Submit a bug report (convenience method)
         * @param {string} message - Bug description
         * @param {Object} options - Additional options (rating, email, metadata)
         */
        submitBugReport: function(message, options) {
            options = options || {};
            options.category = 'bug';
            return this.submitFeedback(message, options);
        },

        /**
         * Submit a feature request (convenience method)
         * @param {string} message - Feature description
         * @param {Object} options - Additional options (rating, email, metadata)
         */
        submitFeatureRequest: function(message, options) {
            options = options || {};
            options.category = 'feature-request';
            return this.submitFeedback(message, options);
        },

        // =====================================================================
        // Chrome Extension Specific
        // =====================================================================

        trackExtensionEvent: function(eventName, properties) {
            if (!isExtension) {
                log('trackExtensionEvent only works in Chrome extensions');
                return this;
            }
            return this.trackEvent(eventName, 'extension', properties);
        },

        trackInstalled: function(properties) {
            this.info('Extension installed', properties);
            return this.trackExtensionEvent('extension_installed', properties);
        },

        trackUpdated: function(previousVersion, properties) {
            var props = Object.assign({ previous_version: previousVersion }, properties || {});
            this.info('Extension updated from ' + previousVersion, props);
            return this.trackExtensionEvent('extension_updated', props);
        },

        trackPopupOpened: function(properties) {
            return this.trackExtensionEvent('popup_opened', properties);
        }
    };

    // =========================================================================
    // Export
    // =========================================================================

    global.Vitalytics = Vitalytics;

})(typeof window !== 'undefined' ? window : this);
