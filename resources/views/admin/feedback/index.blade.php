<x-app-layout>
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-comment-dots mr-2 text-cyan-500"></i> Feedback Management
                </h1>
                <p class="text-gray-500 text-sm mt-1">View and manage user feedback submissions</p>
            </div>
            <a href="{{ route('admin.system.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-1"></i> Back to System Dashboard
            </a>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
            <div class="text-xs text-gray-500">Total</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['new'] }}</div>
            <div class="text-xs text-gray-500">New</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $stats['in_progress'] }}</div>
            <div class="text-xs text-gray-500">In Progress</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <div class="text-2xl font-bold text-red-600">{{ $stats['bugs'] }}</div>
            <div class="text-xs text-gray-500">Bug Reports</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <div class="text-2xl font-bold text-purple-600">{{ $stats['features'] }}</div>
            <div class="text-xs text-gray-500">Feature Requests</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Type</label>
                <select name="type" class="text-sm border-gray-300 rounded-lg pr-8" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="feedback" {{ $filters['type'] === 'feedback' ? 'selected' : '' }}>Feedback</option>
                    <option value="feature" {{ $filters['type'] === 'feature' ? 'selected' : '' }}>Feature Request</option>
                    <option value="enhancement" {{ $filters['type'] === 'enhancement' ? 'selected' : '' }}>Enhancement</option>
                    <option value="bug" {{ $filters['type'] === 'bug' ? 'selected' : '' }}>Bug Report</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Status</label>
                <select name="status" class="text-sm border-gray-300 rounded-lg pr-8" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="new" {{ $filters['status'] === 'new' ? 'selected' : '' }}>New</option>
                    <option value="reviewed" {{ $filters['status'] === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                    <option value="in_progress" {{ $filters['status'] === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ $filters['status'] === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="declined" {{ $filters['status'] === 'declined' ? 'selected' : '' }}>Declined</option>
                </select>
            </div>
            @if($filters['type'] || $filters['status'])
            <div class="flex items-end">
                <a href="{{ route('admin.feedback.index') }}" class="text-sm text-gray-500 hover:text-gray-700 pb-2">
                    <i class="fas fa-times mr-1"></i> Clear Filters
                </a>
            </div>
            @endif
        </form>
    </div>

    {{-- Feedback List --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="divide-y divide-gray-100">
            @forelse($feedback as $item)
            <div class="p-4 hover:bg-gray-50" x-data="{
                expanded: false,
                status: '{{ $item->status }}',
                notes: '{{ addslashes($item->admin_notes ?? '') }}',
                saving: false,
                async updateStatus(newStatus) {
                    this.saving = true;
                    try {
                        const response = await fetch('{{ route('admin.feedback.update', $item) }}', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify({ status: newStatus })
                        });
                        if (response.ok) {
                            this.status = newStatus;
                        }
                    } catch (e) {
                        console.error(e);
                    }
                    this.saving = false;
                }
            }">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0 cursor-pointer" @click="expanded = !expanded">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                @if($item->type === 'bug') bg-red-100 text-red-700
                                @elseif($item->type === 'feature') bg-purple-100 text-purple-700
                                @elseif($item->type === 'enhancement') bg-blue-100 text-blue-700
                                @else bg-gray-100 text-gray-700
                                @endif">
                                @if($item->type === 'bug')<i class="fas fa-bug mr-1"></i>
                                @elseif($item->type === 'feature')<i class="fas fa-lightbulb mr-1"></i>
                                @elseif($item->type === 'enhancement')<i class="fas fa-magic mr-1"></i>
                                @else<i class="fas fa-comment mr-1"></i>
                                @endif
                                {{ ucfirst($item->type) }}
                            </span>
                            <span class="text-xs text-gray-400 font-mono">{{ $item->feedback_id }}</span>
                            <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform" :class="{ 'rotate-180': expanded }"></i>
                        </div>
                        <p class="text-sm text-gray-900 mb-2" x-show="!expanded">{{ \Illuminate\Support\Str::limit($item->message, 150) }}</p>
                        <p class="text-sm text-gray-900 mb-2 whitespace-pre-wrap" x-show="expanded" x-cloak>{{ $item->message }}</p>
                        <div class="flex items-center gap-4 text-xs text-gray-400">
                            <span>
                                <i class="fas fa-user mr-1"></i>
                                {{ $item->user?->name ?? 'Unknown' }}
                                @if($item->user?->email)
                                <span class="text-gray-300">({{ $item->user->email }})</span>
                                @endif
                            </span>
                            <span>
                                <i class="fas fa-clock mr-1"></i>
                                {{ $item->created_at->diffForHumans() }}
                            </span>
                            @if($item->page_url)
                            <span class="truncate max-w-xs" title="{{ $item->page_url }}">
                                <i class="fas fa-link mr-1"></i>
                                {{ parse_url($item->page_url, PHP_URL_PATH) ?: $item->page_url }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <select x-model="status" @change="updateStatus(status)" :disabled="saving"
                                class="text-xs border-gray-300 rounded-lg pr-6"
                                :class="{
                                    'bg-yellow-50 text-yellow-700': status === 'new',
                                    'bg-gray-50 text-gray-700': status === 'reviewed',
                                    'bg-blue-50 text-blue-700': status === 'in_progress',
                                    'bg-green-50 text-green-700': status === 'completed',
                                    'bg-red-50 text-red-700': status === 'declined'
                                }">
                            <option value="new">New</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="declined">Declined</option>
                        </select>
                        <form action="{{ route('admin.feedback.destroy', $item) }}" method="POST" class="inline"
                              onsubmit="return confirm('Are you sure you want to delete this feedback?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600 p-1" title="Delete">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Admin Notes (expanded) --}}
                <div x-show="expanded" x-cloak class="mt-4 pt-4 border-t border-gray-100">
                    @if($item->admin_notes)
                    <div class="bg-gray-50 rounded-lg p-3 mb-3">
                        <div class="text-xs font-medium text-gray-500 mb-1">
                            <i class="fas fa-sticky-note mr-1"></i> Admin Notes
                        </div>
                        <p class="text-sm text-gray-700">{{ $item->admin_notes }}</p>
                    </div>
                    @endif
                    <div class="text-xs text-gray-400">
                        Created: {{ $item->created_at->format('M j, Y g:i A') }}
                        @if($item->updated_at->ne($item->created_at))
                        &bull; Updated: {{ $item->updated_at->format('M j, Y g:i A') }}
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                <p>No feedback found</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    @if($feedback->hasPages())
    <div class="mt-6">
        {{ $feedback->links() }}
    </div>
    @endif
</x-app-layout>
