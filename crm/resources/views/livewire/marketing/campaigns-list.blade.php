<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Campaigns') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session()->has('message'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('message') }}</span>
                </div>
            @endif

            <div class="glass-panel overflow-hidden">
                <div class="p-6 text-gray-900 border-b border-white/50">
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex space-x-4 w-1/2">
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search campaigns..." class="glass-input w-2/3" />
                            <select wire:model.live="statusFilter" class="glass-input w-1/3">
                                <option value="">All Statuses</option>
                                <option value="draft">Draft</option>
                                <option value="running">Running</option>
                                <option value="paused">Paused</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <a href="{{ route('marketing.campaign.create') }}" wire:navigate class="glass-button-primary">
                            + New Campaign
                        </a>
                    </div>

                    <div class="glass-table-container">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Template</th>
                                    <th>Status</th>
                                    <th>Recipients</th>
                                    <th>Created</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($campaigns as $campaign)
                                    <tr>
                                        <td>
                                            <div class="font-medium text-gray-900">{{ $campaign->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $campaign->project }}</div>
                                        </td>
                                        <td class="text-gray-600">
                                            {{ $campaign->template_name }}
                                        </td>
                                        <td>
                                            <span class="glass-badge 
                                                {{ $campaign->status === 'completed' ? 'glass-badge-success' : 
                                                  ($campaign->status === 'draft' ? '' : 
                                                  ($campaign->status === 'running' ? 'glass-badge-info' : 
                                                  ($campaign->status === 'paused' ? 'glass-badge-warning' : 'glass-badge-danger'))) }}">
                                                {{ ucfirst($campaign->status) }}
                                            </span>
                                        </td>
                                        <td class="text-gray-600">
                                            {{ $campaign->sent_count }} / {{ $campaign->total_recipients }}
                                        </td>
                                        <td class="text-gray-600">
                                            {{ $campaign->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="text-right space-x-2">
                                            
                                            <a href="{{ route('marketing.campaign.details', $campaign->id) }}" wire:navigate class="text-indigo-600 hover:text-indigo-900 font-medium">View</a>
                                            
                                            @if($campaign->status === 'draft')
                                                <button wire:click="sendCampaign({{ $campaign->id }})" class="text-green-600 hover:text-green-900 ml-2">Send</button>
                                            @elseif($campaign->status === 'running')
                                                <button wire:click="pauseCampaign({{ $campaign->id }})" class="text-yellow-600 hover:text-yellow-900 ml-2">Pause</button>
                                            @elseif($campaign->status === 'paused')
                                                <button wire:click="resumeCampaign({{ $campaign->id }})" class="text-green-600 hover:text-green-900 ml-2">Resume</button>
                                            @endif

                                            @if(in_array($campaign->status, ['draft', 'running', 'paused']))
                                                <button wire:click="cancelCampaign({{ $campaign->id }})" onclick="confirm('Are you sure you want to cancel this campaign? This cannot be undone.') || event.stopImmediatePropagation()" class="text-red-600 hover:text-red-900 ml-2">Cancel</button>
                                            @endif
                                            
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No campaigns found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $campaigns->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
