<x-app-layout>
<div class="max-w-6xl mx-auto">
    {{-- Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-500 p-6 sm:p-8 mb-8 shadow-xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>

        <div class="relative">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                    <i class="fas fa-palette text-2xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white">Product Icons</h1>
                    <p class="text-white/80 text-sm">Customize icons for your products on the dashboard</p>
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

    {{-- Products Grid --}}
    <div class="grid gap-6">
        @foreach($products as $productId => $product)
        <div x-data="{
            editing: false,
            iconType: '{{ $product['has_custom'] ? ($product['custom_icon_path'] ? 'upload' : 'url') : 'default' }}',
            previewUrl: '{{ $product['custom_icon'] ?? '' }}'
        }" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            {{-- Product Header --}}
            <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    {{-- Current Icon Preview --}}
                    <div class="w-16 h-16 rounded-xl shadow-sm flex items-center justify-center"
                         style="background: linear-gradient(135deg, {{ $product['custom_color'] ?? $product['default_color'] }}20, {{ $product['custom_color'] ?? $product['default_color'] }}40);">
                        @if($product['has_custom'] && $product['custom_icon'])
                            <img src="{{ $product['custom_icon'] }}" alt="{{ $product['name'] }}" class="w-10 h-10 object-contain">
                        @else
                            <i class="fas {{ $product['default_icon'] }} text-2xl" style="color: {{ $product['default_color'] }};"></i>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">{{ $product['name'] }}</h3>
                        <p class="text-sm text-gray-500">{{ $product['description'] }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            @if($product['has_custom'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                    <i class="fas fa-image mr-1"></i> Custom Icon
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    <i class="fas fa-icons mr-1"></i> Default Icon
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <button @click="editing = !editing"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition"
                        :class="editing ? 'bg-gray-100 text-gray-700' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'">
                    <i class="fas" :class="editing ? 'fa-times' : 'fa-edit'"></i>
                    <span x-text="editing ? 'Cancel' : 'Edit Icon'"></span>
                </button>
            </div>

            {{-- Edit Form --}}
            <div x-show="editing"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="border-t border-gray-100 bg-gray-50 p-6">

                <form action="{{ route('admin.icons.update', $productId) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Icon Type Selection --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Icon Source</label>
                        <div class="flex flex-wrap gap-3">
                            <label class="flex items-center gap-2 px-4 py-2 rounded-lg border cursor-pointer transition"
                                   :class="iconType === 'default' ? 'bg-indigo-50 border-indigo-300 text-indigo-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="icon_type" value="default" x-model="iconType" class="sr-only">
                                <i class="fas fa-icons"></i>
                                <span class="text-sm font-medium">Default Icon</span>
                            </label>
                            <label class="flex items-center gap-2 px-4 py-2 rounded-lg border cursor-pointer transition"
                                   :class="iconType === 'upload' ? 'bg-indigo-50 border-indigo-300 text-indigo-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="icon_type" value="upload" x-model="iconType" class="sr-only">
                                <i class="fas fa-upload"></i>
                                <span class="text-sm font-medium">Upload Image</span>
                            </label>
                            <label class="flex items-center gap-2 px-4 py-2 rounded-lg border cursor-pointer transition"
                                   :class="iconType === 'url' ? 'bg-indigo-50 border-indigo-300 text-indigo-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="icon_type" value="url" x-model="iconType" class="sr-only">
                                <i class="fas fa-link"></i>
                                <span class="text-sm font-medium">Image URL</span>
                            </label>
                        </div>
                    </div>

                    {{-- Upload Field --}}
                    <div x-show="iconType === 'upload'" x-transition class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Icon Image</label>
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center bg-white">
                                <template x-if="previewUrl && iconType === 'upload'">
                                    <img :src="previewUrl" class="w-12 h-12 object-contain">
                                </template>
                                <template x-if="!previewUrl || iconType !== 'upload'">
                                    <i class="fas fa-image text-2xl text-gray-400"></i>
                                </template>
                            </div>
                            <div class="flex-1">
                                <input type="file" name="icon_file" accept="image/*"
                                       @change="previewUrl = URL.createObjectURL($event.target.files[0])"
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="text-xs text-gray-500 mt-1">PNG, JPG, SVG up to 2MB. Recommended: 128x128px or larger, square aspect ratio.</p>
                            </div>
                        </div>
                    </div>

                    {{-- URL Field --}}
                    <div x-show="iconType === 'url'" x-transition class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Icon Image URL</label>
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center bg-white">
                                <template x-if="iconType === 'url'">
                                    <img :src="$refs.iconUrl?.value || '{{ $product['custom_icon_url'] ?? '' }}'"
                                         class="w-12 h-12 object-contain"
                                         onerror="this.style.display='none'"
                                         onload="this.style.display='block'">
                                </template>
                            </div>
                            <div class="flex-1">
                                <input type="url" name="icon_url" x-ref="iconUrl"
                                       value="{{ $product['custom_icon_url'] ?? '' }}"
                                       placeholder="https://example.com/icon.png"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <p class="text-xs text-gray-500 mt-1">Enter a direct URL to an image file.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Color Override --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Background Color (Optional)</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="color" value="{{ $product['custom_color'] ?? $product['default_color'] }}"
                                   class="w-12 h-10 rounded-lg border border-gray-300 cursor-pointer">
                            <input type="text"
                                   value="{{ $product['custom_color'] ?? $product['default_color'] }}"
                                   class="w-32 rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-mono"
                                   readonly>
                            <span class="text-sm text-gray-500">This color is used for the icon background gradient.</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between">
                        @if($product['has_custom'])
                        <button type="button"
                                onclick="if(confirm('Remove custom icon and revert to default?')) { document.getElementById('delete-{{ $productId }}').submit(); }"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition">
                            <i class="fas fa-trash"></i>
                            <span>Remove Custom Icon</span>
                        </button>
                        @else
                        <div></div>
                        @endif

                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-sm">
                            <i class="fas fa-save"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </form>

                @if($product['has_custom'])
                <form id="delete-{{ $productId }}" action="{{ route('admin.icons.destroy', $productId) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Help Section --}}
    <div class="mt-8 bg-blue-50 rounded-xl border border-blue-100 p-6">
        <h3 class="font-semibold text-blue-900 flex items-center gap-2 mb-3">
            <i class="fas fa-info-circle"></i>
            Tips for Custom Icons
        </h3>
        <ul class="text-sm text-blue-800 space-y-2">
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-500 mt-1"></i>
                <span>Use square images (1:1 aspect ratio) for best results</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-500 mt-1"></i>
                <span>PNG with transparent background works best</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-500 mt-1"></i>
                <span>Recommended size: 128x128 pixels or larger</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-500 mt-1"></i>
                <span>SVG format provides best quality at any size</span>
            </li>
        </ul>
    </div>
</div>
</x-app-layout>
