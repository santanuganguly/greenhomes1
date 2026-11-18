<div class="py-12" wire:poll="loadLeads">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="glass-panel overflow-hidden">
            <div class="p-6 text-gray-900 border-b border-white/50 flex justify-between items-center">
                <h2 class="text-xl font-bold">Leads Management</h2>
            </div>
            
            <div class="glass-table-container m-6">
                <table class="glass-table">
                    <thead>
                        <tr>
                            <th class="px-6 py-3">Customer</th>
                            <th class="px-6 py-3">Phone</th>
                            <th class="px-6 py-3">Vertical</th>
                            <th class="px-6 py-3">Requirements</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leads as $lead)
                            <tr>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ $lead->customer_name ?: 'Unknown' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $lead->phone_number }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="glass-badge-success">
                                        {{ $lead->business_vertical ?: 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    @if($lead->budget) <div class="font-bold text-green-600">Budget: {{ $lead->budget }}</div> @endif
                                    @if($lead->timeline) <div>Timeline: {{ $lead->timeline }}</div> @endif
                                    @if($lead->home_type) <div>Type: {{ $lead->home_type }} ({{ $lead->floors }})</div> @endif
                                    @if($lead->project) <div>Project: {{ $lead->project }}</div> @endif
                                    @if(!$lead->budget && !$lead->project && !$lead->home_type)
                                        <span class="text-gray-400 italic">Gathering info...</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="glass-badge">
                                        {{ ucfirst($lead->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('inbox') }}" class="text-green-600 hover:underline">View Chat</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No leads captured yet. When someone messages the bot, they will appear here.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
