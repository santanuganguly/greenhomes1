

<div class="py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-6 flex justify-between items-end">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Bulk Messaging</h1>
                <p class="text-sm text-gray-500 mt-1">Upload a CSV file and dispatch personalized WhatsApp campaigns.</p>
            </div>
        </div>

        @if($successMsg)
            <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-3 text-green-800 text-sm font-medium">
                {{ $successMsg }}
            </div>
        @endif
        @if($errorMsg)
            <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-red-800 text-sm font-medium">
                {{ $errorMsg }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <form wire:submit.prevent="createCampaign" class="space-y-4">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Name</label>
                        <input type="text" wire:model="name" class="w-full rounded border-gray-300 text-sm shadow-sm" placeholder="e.g. August Promo" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload CSV (Columns: Name, Phone)</label>
                        <input type="file" wire:model="file" accept=".csv" class="w-full border-gray-300 text-sm shadow-sm p-1.5" required>
                        <div wire:loading wire:target="file" class="text-sm text-blue-500 mt-1">Uploading...</div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message Template</label>
                    <textarea wire:model="message_template" rows="4" class="w-full rounded border-gray-300 text-sm shadow-sm" required></textarea>
                    <p class="text-xs text-gray-500 mt-1">Use <code>{name}</code> to inject the contact's name from the CSV.</p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-green-700 shadow" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="createCampaign">Dispatch Campaign</span>
                        <span wire:loading wire:target="createCampaign">Processing...</span>
                    </button>
                </div>
            </form>
        </div>

        <h2 class="text-lg font-bold text-gray-900 mb-4">Past Campaigns</h2>
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacts</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @if($campaigns)
                        @forelse($campaigns as $camp)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $camp->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $camp->total_contacts }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $camp->created_at->format('M d, Y H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                    <a href="/bulk/{{ $camp->id }}" wire:navigate class="text-green-600 hover:text-green-900 font-medium">View Report</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No bulk campaigns dispatched yet.</td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
