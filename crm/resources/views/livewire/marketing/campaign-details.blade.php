<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Campaign Details') }}: {{ $campaign->name }}
            </h2>
            <a href="{{ route('marketing.campaigns') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-900">Back to Campaigns</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Summary Stats -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-6">
                <div class="glass-panel p-4 text-center">
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Total</div>
                    <div class="mt-1 text-2xl font-bold">{{ $campaign->total_recipients }}</div>
                </div>
                <div class="glass-panel p-4 text-center border-b-4 border-yellow-400">
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Pending/Queued</div>
                    <div class="mt-1 text-2xl font-bold">{{ $campaign->pending_count + $campaign->queued_count }}</div>
                </div>
                <div class="glass-panel p-4 text-center border-b-4 border-blue-400">
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Sent</div>
                    <div class="mt-1 text-2xl font-bold">{{ $campaign->sent_count }}</div>
                </div>
                <div class="glass-panel p-4 text-center border-b-4 border-green-400">
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Delivered</div>
                    <div class="mt-1 text-2xl font-bold">{{ $campaign->delivered_count }}</div>
                </div>
                <div class="glass-panel p-4 text-center border-b-4 border-indigo-400">
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Read</div>
                    <div class="mt-1 text-2xl font-bold">{{ $campaign->read_count }}</div>
                </div>
                <div class="glass-panel p-4 text-center border-b-4 border-red-500">
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Failed</div>
                    <div class="mt-1 text-2xl font-bold text-red-600">{{ $campaign->failed_count }}</div>
                </div>
            </div>

            <!-- Recipients Table -->
            <div class="glass-panel overflow-hidden">
                <div class="p-6 text-gray-900 border-b border-white/50">
                    <h3 class="text-lg font-medium mb-4">Recipients</h3>
                    
                    <div class="flex space-x-4 w-1/2 mb-4">
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search phone or name..." class="glass-input w-2/3" />
                        <select wire:model.live="statusFilter" class="glass-input w-1/3">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="queued">Queued</option>
                            <option value="sent">Sent</option>
                            <option value="delivered">Delivered</option>
                            <option value="read">Read</option>
                            <option value="failed">Failed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="glass-table-container">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Phone / Name</th>
                                    <th>Status</th>
                                    <th>Retries</th>
                                    <th>Error Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recipients as $recipient)
                                    <tr>
                                        <td>
                                            <div class="font-medium">{{ $recipient->phone }}</div>
                                            <div class="text-xs text-gray-500">{{ $recipient->name ?: 'N/A' }}</div>
                                        </td>
                                        <td>
                                            <span class="glass-badge 
                                                {{ $recipient->status === 'read' ? 'glass-badge-info' : 
                                                  ($recipient->status === 'delivered' ? 'glass-badge-success' : 
                                                  ($recipient->status === 'sent' ? 'glass-badge-info' : 
                                                  ($recipient->status === 'failed' ? 'glass-badge-danger' : 'glass-badge-warning'))) }}">
                                                {{ ucfirst($recipient->status) }}
                                            </span>
                                        </td>
                                        <td class="text-gray-600 text-xs">
                                            {{ $recipient->retry_count }}
                                        </td>
                                        <td class="text-xs text-red-600 max-w-xs truncate" title="{{ $recipient->error_message }}">
                                            @if($recipient->status === 'failed')
                                                Code: {{ $recipient->error_code }}<br>
                                                {{ Str::limit($recipient->error_message, 40) }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                            No recipients found matching the filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $recipients->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
