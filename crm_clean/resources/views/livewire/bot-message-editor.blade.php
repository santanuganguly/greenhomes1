<div class="py-8">
 <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

 <!-- Header -->
 <div class="mb-6 flex items-center justify-between">
 <div>
 <h1 class="text-2xl font-bold text-gray-900">WhatsApp Message Library</h1>
 <p class="text-sm text-gray-500 mt-1">All {{ $messages->count() }} pre-configured messages. Edit body, header, footer, and Meta template mapping.</p>
 </div>
 </div>

 @if($successMessage)
 <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-3 text-green-800 text-sm font-medium">
 {{ $successMessage }}
 </div>
 @endif

 <!-- Filters -->
 <div class="mb-4 flex gap-4">
 <input type="text" wire:model.live="search" placeholder="Search messages..."
 class="w-64 rounded-md border-gray-300 shadow-sm text-sm focus:border-green-500 focus:ring-green-500">
 <select wire:model.live="filterGroup" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-green-500 focus:ring-green-500">
 <option value="">All Flow Groups</option>
 @foreach($this->flowGroups as $g)
 <option value="{{ $g }}">{{ $g }}</option>
 @endforeach
 </select>
 </div>

 <!-- Edit Form -->
 @if($editingId)
 @php $editing = $messages->firstWhere('id', $editingId); @endphp
 <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-6 shadow-sm">
 <h2 class="text-lg font-bold text-green-900 mb-4">
 Editing: <span class="font-mono text-sm bg-green-100 px-2 py-0.5 rounded">{{ $editing?->internal_name }}</span>
 </h2>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
 <input type="text" wire:model="editDisplayName" class="w-full rounded border-gray-300 text-sm shadow-sm">
 </div>
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Header Text <span class="text-gray-400">(optional)</span></label>
 <input type="text" wire:model="editHeaderText" class="w-full rounded border-gray-300 text-sm shadow-sm" placeholder="e.g. WELCOME TO GREEN HILL GROUP">
 </div>
 <div class="md:col-span-2">
 <label class="block text-sm font-medium text-gray-700 mb-1">Message Body <span class="text-red-500">*</span></label>
 <textarea wire:model="editBodyText" rows="6" class="w-full rounded border-gray-300 text-sm shadow-sm font-mono"></textarea>
 <p class="text-xs text-gray-500 mt-1">Use {{name}}, {{project}}, {{config}} for dynamic values.</p>
 </div>
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Footer Text <span class="text-gray-400">(optional)</span></label>
 <input type="text" wire:model="editFooterText" class="w-full rounded border-gray-300 text-sm shadow-sm" placeholder="e.g. +91 93393 61344">
 </div>
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Active?</label>
 <label class="flex items-center mt-2">
 <input type="checkbox" wire:model="editIsActive" class="rounded border-gray-300 text-green-600">
 <span class="ml-2 text-sm text-gray-600">Message is active</span>
 </label>
 </div>

 <!-- Meta Template -->
 <div class="md:col-span-2 border-t pt-4 mt-2">
 <h3 class="text-sm font-bold text-gray-800 mb-3"> Meta Template Mapping</h3>
 <div class="grid grid-cols-3 gap-4">
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Meta Template Name</label>
 <input type="text" wire:model="editMetaTemplate" class="w-full rounded border-gray-300 text-sm shadow-sm font-mono" placeholder="e.g. green_hill_welcome">
 <p class="text-xs text-gray-400 mt-1">Must exactly match the approved template name in Meta Business Manager.</p>
 </div>
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Meta Template Status</label>
 <select wire:model="editMetaStatus" class="w-full rounded border-gray-300 text-sm shadow-sm">
 <option value="not_connected">⚫ Not Connected</option>
 <option value="pending">🟡 Pending Approval</option>
 <option value="approved"> Approved</option>
 <option value="rejected"> Rejected</option>
 <option value="paused"> Paused</option>
 </select>
 </div>
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Language Code</label>
 <input type="text" wire:model="editMetaLanguage" class="w-full rounded border-gray-300 text-sm shadow-sm" placeholder="en">
 </div>
 </div>
 </div>
 </div>

 <div class="mt-4 flex gap-3">
 <button wire:click="saveMessage" class="bg-green-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-green-700 shadow">
 Save Changes
 </button>
 <button wire:click="cancelEdit" class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">
 Cancel
 </button>
 </div>
 </div>
 @endif

 <!-- Messages Table -->
 <div class="bg-white rounded-xl shadow-sm overflow-hidden">
 <table class="min-w-full divide-y divide-gray-200 text-sm">
 <thead class="bg-gray-50">
 <tr>
 <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Internal Name</th>
 <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Display Name</th>
 <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Flow Group</th>
 <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
 <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Meta Template</th>
 <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
 <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Buttons</th>
 <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100">
 @forelse($messages as $msg)
 <tr class="{{ $editingId === $msg->id ? 'bg-green-50' : 'hover:bg-gray-50' }}">
 <td class="px-4 py-3">
 <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded text-gray-700">{{ $msg->internal_name }}</span>
 </td>
 <td class="px-4 py-3 font-medium text-gray-800">{{ $msg->display_name }}</td>
 <td class="px-4 py-3">
 <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded">{{ $msg->flow_group ?? '—' }}</span>
 </td>
 <td class="px-4 py-3">
 <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $msg->message_type }}</span>
 </td>
 <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $msg->meta_template_name ?? '—' }}</td>
 <td class="px-4 py-3">
 @php
 $statusColors = [
 'approved' => 'bg-green-100 text-green-700',
 'pending' => 'bg-yellow-100 text-yellow-700',
 'rejected' => 'bg-red-100 text-red-700',
 'paused' => 'bg-orange-100 text-orange-700',
 'not_connected' => 'bg-gray-100 text-gray-600',
 ];
 $statusLabels = [
 'approved' => ' Approved',
 'pending' => '🟡 Pending',
 'rejected' => ' Rejected',
 'paused' => ' Paused',
 'not_connected' => '⚫ Not Connected',
 ];
 @endphp
 <span class="text-xs px-2 py-0.5 rounded {{ $statusColors[$msg->meta_template_status] ?? 'bg-gray-100 text-gray-600' }}">
 {{ $statusLabels[$msg->meta_template_status] ?? $msg->meta_template_status }}
 </span>
 </td>
 <td class="px-4 py-3 text-xs text-gray-500">
 {{ count($msg->buttons ?? []) }} btn(s)
 </td>
 <td class="px-4 py-3">
 <button wire:click="edit({{ $msg->id }})" class="text-green-600 hover:text-green-800 text-xs font-medium">
 Edit
 </button>
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="8" class="px-4 py-8 text-center text-gray-400">No messages found.</td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
 </div>
</div>
