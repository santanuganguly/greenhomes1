<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('WhatsApp Marketing') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Campaign Overview</h3>
                <a href="{{ route('marketing.campaign.create') }}" wire:navigate class="glass-button-primary">
                    Create Campaign
                </a>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="glass-panel p-6">
                    <div class="text-sm font-medium text-gray-600">Total Campaigns</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-900">{{ $totalCampaigns }}</div>
                </div>
                <div class="glass-panel p-6">
                    <div class="text-sm font-medium text-gray-600">Total Recipients</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-900">{{ number_format($totalRecipients) }}</div>
                </div>
                <div class="glass-panel p-6">
                    <div class="text-sm font-medium text-green-600">Delivery Rate</div>
                    <div class="mt-2 text-3xl font-semibold text-green-700">{{ $deliveryRate }}%</div>
                </div>
                <div class="glass-panel p-6">
                    <div class="text-sm font-medium text-blue-600">Read Rate</div>
                    <div class="mt-2 text-3xl font-semibold text-blue-700">{{ $readRate }}%</div>
                </div>
            </div>

            <!-- Recent Campaigns -->
            <div class="glass-panel overflow-hidden">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-medium">Recent Campaigns</h4>
                        <a href="{{ route('marketing.campaigns') }}" wire:navigate class="text-sm text-green-600 hover:text-green-500">View All</a>
                    </div>
                    
                    <div class="glass-table-container m-6">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentCampaigns as $campaign)
                                    <tr>
                                        <td>
                                            <div class="font-medium text-gray-900">{{ $campaign->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $campaign->template_name }}</div>
                                        </td>
                                        <td>
                                            <span class="glass-badge {{ $campaign->status === 'completed' ? 'glass-badge-success' : 
                                                  ($campaign->status === 'draft' ? '' : 
                                                  ($campaign->status === 'running' ? 'glass-badge-info' : 'glass-badge-warning')) }}">
                                                {{ ucfirst($campaign->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $campaign->sent_count }} / {{ $campaign->total_recipients }}
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('marketing.campaign.details', $campaign->id) }}" wire:navigate class="text-indigo-600 hover:text-indigo-900 font-medium">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No campaigns found. Create your first campaign to get started.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
