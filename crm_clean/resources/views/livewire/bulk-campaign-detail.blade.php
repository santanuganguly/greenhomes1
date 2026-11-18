<div class="py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-6 flex justify-between items-center">
            <div>
                <a href="/bulk" wire:navigate class="text-sm text-green-600 hover:text-green-800 mb-2 inline-block">&larr; Back to Campaigns</a>
                <h1 class="text-2xl font-bold text-gray-900">Campaign: {{ $campaign->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">Dispatched on {{ $campaign->created_at->format('M d, Y H:i') }} | Total Contacts: {{ $campaign->total_contacts }}</p>
            </div>
            <div>
                <button wire:click="$refresh" class="bg-gray-100 text-gray-700 px-4 py-2 rounded font-medium hover:bg-gray-200 text-sm border border-gray-300">
                    ↻ Refresh Statuses
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Error (if any)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($messages as $msg)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $msg->contact_name ?: '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $msg->phone_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($msg->status === 'delivered')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Delivered</span>
                                @elseif($msg->status === 'read')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Read</span>
                                @elseif($msg->status === 'sent')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Sent</span>
                                @elseif($msg->status === 'pending')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-red-500 max-w-xs truncate" title="{{ $msg->error_message }}">{{ $msg->error_message }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $msg->updated_at->format('H:i:s') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
    </div>
</div>
