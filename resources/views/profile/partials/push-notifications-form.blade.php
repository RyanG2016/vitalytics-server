<section x-data="{
    devices: [],
    loading: true,
    sending: false,
    message: null,
    messageType: 'success',
    async loadDevices() {
        try {
            const response = await fetch('{{ route('mobile.device-tokens.index') }}', {
                headers: { 'Accept': 'application/json' },
                credentials: 'include'
            });
            const data = await response.json();
            this.devices = data.devices || [];
        } catch (e) {
            console.error('Failed to load devices:', e);
        }
        this.loading = false;
    },
    async sendTest() {
        this.sending = true;
        this.message = null;
        try {
            const response = await fetch('{{ route('mobile.test-push') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json'
                },
                credentials: 'include'
            });
            const data = await response.json();
            if (data.success) {
                this.message = 'Test notification sent! Check your device.';
                this.messageType = 'success';
            } else {
                this.message = data.message || 'Failed to send notification';
                this.messageType = 'error';
            }
        } catch (e) {
            this.message = 'Failed to send notification. Please try again.';
            this.messageType = 'error';
        }
        this.sending = false;
    },
    async removeDevice(token) {
        if (!confirm('Remove this device?')) return;
        try {
            const response = await fetch('/mobile/device-tokens/' + encodeURIComponent(token), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json'
                },
                credentials: 'include'
            });
            const data = await response.json();
            if (data.success) {
                this.devices = this.devices.filter(d => d.device_token !== token);
                this.message = 'Device removed successfully';
                this.messageType = 'success';
            }
        } catch (e) {
            this.message = 'Failed to remove device';
            this.messageType = 'error';
        }
    },
    async updatePreference(deviceId, field, value) {
        try {
            const response = await fetch('/mobile/device-tokens/' + deviceId + '/preferences', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({ [field]: value })
            });
            const data = await response.json();
            if (data.success) {
                const device = this.devices.find(d => d.id === deviceId);
                if (device) device[field] = value;
            } else {
                this.message = 'Failed to update preference';
                this.messageType = 'error';
            }
        } catch (e) {
            this.message = 'Failed to update preference';
            this.messageType = 'error';
        }
    },
    formatDate(dateStr) {
        if (!dateStr) return 'Never';
        return new Date(dateStr).toLocaleDateString('en-US', {
            month: 'short', day: 'numeric', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }
}" x-init="loadDevices()">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Push Notifications') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Manage your registered devices and test push notifications.') }}
        </p>
    </header>

    <!-- Status Message -->
    <div x-show="message" x-cloak class="mt-4">
        <div :class="messageType === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'"
             class="px-4 py-3 rounded-lg border text-sm">
            <span x-text="message"></span>
        </div>
    </div>

    <!-- Registered Devices -->
    <div class="mt-6">
        <h3 class="text-sm font-medium text-gray-700 mb-3">Registered Devices</h3>

        <!-- Loading State -->
        <div x-show="loading" class="text-sm text-gray-500">
            <i class="fas fa-spinner fa-spin mr-1"></i> Loading devices...
        </div>

        <!-- No Devices -->
        <div x-show="!loading && devices.length === 0" class="text-sm text-gray-500 bg-gray-50 rounded-lg p-4">
            <i class="fas fa-mobile-alt mr-1"></i>
            No devices registered. Open Vitalytics on your mobile device to register for push notifications.
        </div>

        <!-- Device List -->
        <div x-show="!loading && devices.length > 0" class="space-y-3">
            <template x-for="device in devices" :key="device.id">
                <div class="bg-gray-50 rounded-lg px-4 py-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="text-gray-400">
                                <i :class="device.platform === 'ios' ? 'fab fa-apple text-xl' : 'fab fa-android text-xl text-green-600'"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900" x-text="device.device_name || (device.platform === 'ios' ? 'iPhone' : 'Android Device')"></div>
                                <div class="text-xs text-gray-500">
                                    <span x-text="device.platform === 'ios' ? 'iOS' : 'Android'"></span>
                                    <span class="mx-1">•</span>
                                    <span>Last used: <span x-text="formatDate(device.last_used_at)"></span></span>
                                </div>
                            </div>
                        </div>
                        <button @click="removeDevice(device.device_token)"
                                class="text-gray-400 hover:text-red-600 transition"
                                title="Remove device">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <!-- Notification Preferences -->
                    <div class="mt-3 pt-3 border-t border-gray-200 flex flex-wrap gap-4">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox"
                                   :checked="device.health_alerts"
                                   @change="updatePreference(device.id, 'health_alerts', $event.target.checked)"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="text-sm text-gray-700">
                                <i class="fas fa-heartbeat text-red-500 mr-1"></i>Health Alerts
                            </span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox"
                                   :checked="device.feedback_alerts"
                                   @change="updatePreference(device.id, 'feedback_alerts', $event.target.checked)"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="text-sm text-gray-700">
                                <i class="fas fa-comment text-blue-500 mr-1"></i>Feedback Alerts
                            </span>
                        </label>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Test Push Button -->
    <div class="mt-6">
        <button @click="sendTest()"
                :disabled="sending || devices.length === 0"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
            <template x-if="!sending">
                <span><i class="fas fa-bell mr-2"></i>Send Test Notification</span>
            </template>
            <template x-if="sending">
                <span><i class="fas fa-spinner fa-spin mr-2"></i>Sending...</span>
            </template>
        </button>
        <p x-show="devices.length === 0 && !loading" class="mt-2 text-xs text-gray-500">
            Register a device first to test push notifications.
        </p>
    </div>
</section>
