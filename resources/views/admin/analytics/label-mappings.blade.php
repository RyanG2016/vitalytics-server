<x-app-layout>
<div class="max-w-6xl mx-auto">
    {{-- Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 p-6 sm:p-8 mb-8 shadow-xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>

        <div class="relative">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                    <i class="fas fa-tags text-2xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white">Event Label Mappings</h1>
                    <p class="text-white/80 text-sm">Map technical event names to friendly labels for the dashboard</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-check text-green-600"></i>
        </div>
        <p class="text-green-800">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-exclamation-circle text-red-600"></i>
        </div>
        <p class="text-red-800">{{ session('error') }}</p>
    </div>
    @endif

    {{-- App and Type Selection --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <form method="GET" action="{{ route('admin.label-mappings.index') }}" class="flex flex-wrap gap-4 items-end">
            {{-- App Selection --}}
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select App</label>
                <select name="app" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">-- Select an app --</option>
                    @foreach($products as $productId => $product)
                        <optgroup label="{{ $product['name'] }}">
                            @foreach($apps as $appId => $app)
                                @if($app['product'] === $productId)
                                    <option value="{{ $appId }}" {{ $selectedApp === $appId ? 'selected' : '' }}>
                                        {{ $app['name'] }}
                                    </option>
                                @endif
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            {{-- Type Selection --}}
            @if($selectedApp)
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-2">Mapping Type</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($types as $type)
                        <button type="submit" name="type" value="{{ $type }}"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $selectedType === $type ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            @if($type === 'screen')
                                <i class="fas fa-desktop mr-1"></i>
                            @elseif($type === 'element')
                                <i class="fas fa-mouse-pointer mr-1"></i>
                            @elseif($type === 'feature')
                                <i class="fas fa-star mr-1"></i>
                            @elseif($type === 'form')
                                <i class="fas fa-clipboard-list mr-1"></i>
                            @elseif($type === 'event_type')
                                <i class="fas fa-bolt mr-1"></i>
                            @endif
                            {{ ucfirst(str_replace('_', ' ', $type)) }}s
                        </button>
                    @endforeach
                </div>
                <input type="hidden" name="app" value="{{ $selectedApp }}">
            </div>
            @endif
        </form>
    </div>

    @if($selectedApp)
    {{-- Existing Mappings --}}
    @if($mappings->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-check-circle text-green-500"></i>
                Mapped {{ ucfirst(str_replace('_', ' ', $selectedType)) }}s
                <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-sm">{{ $mappings->count() }}</span>
            </h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($mappings as $mapping)
            <div x-data="{
                editing: false,
                friendlyLabel: '{{ addslashes($mapping->friendly_label) }}',
                originalLabel: '{{ addslashes($mapping->friendly_label) }}',
                saving: false,
                async saveMapping() {
                    this.saving = true;
                    try {
                        const response = await fetch('{{ route('admin.label-mappings.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                app_identifier: '{{ $selectedApp }}',
                                mapping_type: '{{ $selectedType }}',
                                raw_value: '{{ addslashes($mapping->raw_value) }}',
                                friendly_label: this.friendlyLabel
                            })
                        });
                        const data = await response.json();
                        this.editing = false;
                        this.originalLabel = this.friendlyLabel;
                    } catch (err) {
                        alert('Failed to save');
                    }
                    this.saving = false;
                },
                cancelEdit() {
                    this.editing = false;
                    this.friendlyLabel = this.originalLabel;
                }
            }" class="px-6 py-4 hover:bg-gray-50 transition">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3">
                            <code class="px-2 py-1 bg-gray-100 rounded text-sm text-gray-700 truncate max-w-xs" title="{{ $mapping->raw_value }}">{{ $mapping->raw_value }}</code>
                            <i class="fas fa-arrow-right text-gray-400"></i>
                            <template x-if="!editing">
                                <span class="font-medium text-gray-900" x-text="friendlyLabel"></span>
                            </template>
                            <template x-if="editing">
                                <input type="text" x-model="friendlyLabel" @keydown.enter="saveMapping()" @keydown.escape="cancelEdit()"
                                       class="flex-1 rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                       :disabled="saving">
                            </template>
                        </div>
                        @if($mapping->client_suggested_label)
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-lightbulb text-yellow-500 mr-1"></i>
                            Client suggested: {{ $mapping->client_suggested_label }}
                        </p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <template x-if="!editing">
                            <button @click="editing = true" class="p-2 text-gray-400 hover:text-indigo-600 transition" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                        </template>
                        <template x-if="editing">
                            <div class="flex gap-2">
                                <button @click="saveMapping()" :disabled="saving" class="p-2 text-green-600 hover:text-green-700 transition" title="Save">
                                    <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                </button>
                                <button @click="cancelEdit()" class="p-2 text-gray-400 hover:text-gray-600 transition" title="Cancel">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </template>
                        <button @click="if(confirm('Delete this mapping?')) { deleteMapping({{ $mapping->id }}) }" class="p-2 text-gray-400 hover:text-red-600 transition" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Unmapped Values --}}
    @if(count($unmappedValues) > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-amber-50">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-question-circle text-amber-500"></i>
                Unmapped {{ ucfirst(str_replace('_', ' ', $selectedType)) }}s
                <span class="ml-2 px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-sm">{{ count($unmappedValues) }}</span>
            </h2>
            <p class="text-sm text-gray-600 mt-1">These values have been recorded but don't have friendly labels yet.</p>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($unmappedValues as $value)
            @php
                $clientSuggested = $clientLabels[$value['raw_value']] ?? null;
            @endphp
            <div x-data="{
                editing: false,
                friendlyLabel: '{{ addslashes($clientSuggested ?? '') }}',
                saving: false,
                saved: false,
                async saveNewMapping() {
                    if (!this.friendlyLabel || !this.friendlyLabel.trim()) return;
                    this.saving = true;
                    try {
                        const response = await fetch('{{ route('admin.label-mappings.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                app_identifier: '{{ $selectedApp }}',
                                mapping_type: '{{ $selectedType }}',
                                raw_value: '{{ addslashes($value['raw_value']) }}',
                                friendly_label: this.friendlyLabel,
                                client_suggested_label: '{{ addslashes($clientSuggested ?? '') }}'
                            })
                        });
                        const data = await response.json();
                        this.editing = false;
                        this.saved = true;
                    } catch (err) {
                        alert('Failed to save');
                    }
                    this.saving = false;
                },
                startEditing() {
                    this.editing = true;
                }
            }" class="px-6 py-4 hover:bg-gray-50 transition" :class="saved ? 'bg-green-50' : ''">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <code class="px-2 py-1 bg-gray-100 rounded text-sm text-gray-700 truncate max-w-xs" title="{{ $value['raw_value'] }}">{{ $value['raw_value'] }}</code>
                            <template x-if="editing || saved">
                                <i class="fas fa-arrow-right text-gray-400"></i>
                            </template>
                            <template x-if="editing && !saved">
                                <input type="text" x-model="friendlyLabel" @keydown.enter="saveNewMapping()" @keydown.escape="editing = false"
                                       class="flex-1 min-w-[200px] rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                       placeholder="Enter friendly label..."
                                       :disabled="saving"
                                       x-ref="labelInput">
                            </template>
                            <template x-if="saved">
                                <span class="font-medium text-green-700" x-text="friendlyLabel"></span>
                            </template>
                        </div>
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                            <span><i class="fas fa-chart-bar mr-1"></i> {{ number_format($value['usage_count']) }} uses</span>
                            <span><i class="fas fa-clock mr-1"></i> Last: {{ \Carbon\Carbon::parse($value['last_used'])->diffForHumans() }}</span>
                            @if($clientSuggested)
                            <span class="text-yellow-600"><i class="fas fa-lightbulb mr-1"></i> Client suggested: {{ $clientSuggested }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <template x-if="!editing && !saved">
                            <button @click="startEditing(); $nextTick(() => { if ($refs.labelInput) $refs.labelInput.focus() })" class="px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-medium hover:bg-indigo-100 transition">
                                <i class="fas fa-plus mr-1"></i> Add Label
                            </button>
                        </template>
                        <template x-if="editing && !saved">
                            <div class="flex gap-2">
                                <button @click="saveNewMapping()" :disabled="saving || !friendlyLabel || !friendlyLabel.trim()" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition disabled:opacity-50">
                                    <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i> Save
                                </button>
                                <button @click="editing = false" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                                    Cancel
                                </button>
                            </div>
                        </template>
                        <template x-if="saved">
                            <span class="text-green-600 text-sm font-medium"><i class="fas fa-check-circle mr-1"></i> Saved</span>
                        </template>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @elseif($mappings->count() === 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-inbox text-2xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No {{ str_replace('_', ' ', $selectedType) }}s found</h3>
        <p class="text-gray-500">There are no {{ str_replace('_', ' ', $selectedType) }}s recorded for this app yet.<br>Start using your app to generate analytics events.</p>
    </div>
    @else
    <div class="bg-green-50 rounded-xl border border-green-200 p-6 text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-check-circle text-2xl text-green-600"></i>
        </div>
        <h3 class="text-lg font-medium text-green-900 mb-2">All {{ str_replace('_', ' ', $selectedType) }}s are mapped!</h3>
        <p class="text-green-700">Every {{ str_replace('_', ' ', $selectedType) }} in your app has a friendly label.</p>
    </div>
    @endif

    @else
    {{-- No app selected --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-hand-pointer text-2xl text-indigo-600"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Select an App to Start</h3>
        <p class="text-gray-500">Choose an app from the dropdown above to manage its event label mappings.</p>
    </div>
    @endif

    {{-- Help Section --}}
    <div class="mt-8 bg-blue-50 rounded-xl border border-blue-100 p-6">
        <h3 class="font-semibold text-blue-900 flex items-center gap-2 mb-3">
            <i class="fas fa-info-circle"></i>
            About Label Mappings
        </h3>
        <ul class="text-sm text-blue-800 space-y-2">
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-500 mt-1"></i>
                <span><strong>Screen</strong> - Map screen names like "PatientDetailView" to "Patient Details"</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-500 mt-1"></i>
                <span><strong>Element</strong> - Map button/element IDs like "btn_save" to "Save Button"</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-500 mt-1"></i>
                <span><strong>Feature</strong> - Map feature identifiers like "dark_mode" to "Dark Mode Toggle"</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-500 mt-1"></i>
                <span><strong>Form</strong> - Map form names like "patient_form" to "Patient Registration Form"</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-500 mt-1"></i>
                <span><strong>Event Type</strong> - Map event types like "button_clicked" to "Button Click"</span>
            </li>
            <li class="flex items-start gap-2 mt-4 pt-2 border-t border-blue-200">
                <i class="fas fa-lightbulb text-yellow-500 mt-1"></i>
                <span>Labels sent by your app (via SDK) will appear as suggestions but can be overridden here.</span>
            </li>
        </ul>
    </div>
</div>

<script>
function deleteMapping(id) {
    fetch(`{{ url('admin/label-mappings') }}/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        window.location.reload();
    })
    .catch(err => {
        alert('Failed to delete');
    });
}
</script>
</x-app-layout>
