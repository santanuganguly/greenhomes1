<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">💬 WhatsApp Message Library</h1>
                <p class="text-sm text-gray-500 mt-1">All {{ $messages->count() }} pre-configured messages. Edit body, header, footer, and Meta template mapping.</p>
            </div>
        </div>

        @if($successMessage)
        <div class="mb-4 bg-green-100/60 border border-green-200/60 backdrop-blur-sm rounded-lg p-3 text-green-800 text-sm font-medium">
            {{ $successMessage }}
        </div>
        @endif

        <!-- Filters -->
        <div class="mb-4 flex gap-4">
            <input type="text" wire:model.live="search" placeholder="Search messages..."
                class="glass-input w-64">
            <select wire:model.live="filterGroup" class="glass-input w-48">
                <option value="">All Flow Groups</option>
                @foreach($this->flowGroups as $g)
                    <option value="{{ $g }}">{{ $g }}</option>
                @endforeach
            </select>
        </div>

        <!-- Edit Form -->
        @if($editingId)
        @php $editing = $messages->firstWhere('id', $editingId); @endphp
        <div class="mb-6 bg-green-100/60 border border-green-200/60 backdrop-blur-sm rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-green-900 mb-4">
                ✏️ Editing: <span class="font-mono text-sm bg-white/50 border border-white/60 px-2 py-0.5 rounded">{{ $editing?->internal_name }}</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
                    <input type="text" wire:model="editDisplayName" class="glass-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Header Text <span class="text-gray-400">(optional)</span></label>
                    <input type="text" wire:model="editHeaderText" class="glass-input" placeholder="e.g. WELCOME TO GREEN HILL GROUP">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message Body <span class="text-red-500">*</span></label>
                    <textarea wire:model="editBodyText" rows="6" class="glass-input font-mono"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Use {{name}}, {{project}}, {{config}} for dynamic values.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Footer Text <span class="text-gray-400">(optional)</span></label>
                    <input type="text" wire:model="editFooterText" class="glass-input" placeholder="e.g. 📞 +91 93393 61344">
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
                    <h3 class="text-sm font-bold text-gray-800 mb-3">🔗 Meta Template Mapping</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Template Name</label>
                            <input type="text" wire:model="editMetaTemplate" class="glass-input font-mono" placeholder="e.g. green_hill_welcome">
                            <p class="text-xs text-gray-400 mt-1">Must exactly match the approved template name in Meta Business Manager.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Template Status</label>
                            <select wire:model="editMetaStatus" class="glass-input">
                                <option value="not_connected">⚫ Not Connected</option>
                                <option value="pending">🟡 Pending Approval</option>
                                <option value="approved">🟢 Approved</option>
                                <option value="rejected">🔴 Rejected</option>
                                <option value="paused">🟠 Paused</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Language Code</label>
                            <input type="text" wire:model="editMetaLanguage" class="glass-input" placeholder="en">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <button wire:click="saveMessage" class="glass-button-primary">
                    💾 Save Changes
                </button>
                <button wire:click="cancelEdit" class="glass-button">
                    Cancel
                </button>
            </div>
        </div>
        @endif

        <!-- Messages Table -->
        <div class="glass-table-container">
            <table class="glass-table">
                <thead>
                    <tr>
                        <th>Internal Name</th>
                        <th>Display Name</th>
                        <th>Flow Group</th>
                        <th>Type</th>
                        <th>Meta Template</th>
                        <th>Status</th>
                        <th>Buttons</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $msg)
                    <tr>
                        <td>
                            <span class="font-mono text-xs bg-white/50 px-1.5 py-0.5 rounded text-gray-700">{{ $msg->internal_name }}</span>
                        </td>
                        <td class="font-medium text-gray-800">{{ $msg->display_name }}</td>
                        <td>
                            <span class="text-xs bg-purple-100/60 text-purple-700 px-2 py-0.5 rounded backdrop-blur-sm">{{ $msg->flow_group ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="text-xs bg-white/50 text-gray-600 px-2 py-0.5 rounded backdrop-blur-sm">{{ $msg->message_type }}</span>
                        </td>
                        <td class="font-mono text-xs text-gray-600">{{ $msg->meta_template_name ?? '—' }}</td>
                        <td>
                            @php
                                $statusColors = [
                                    'approved'      => 'glass-badge-success',
                                    'pending'       => 'glass-badge-warning',
                                    'rejected'      => 'glass-badge-danger',
                                    'paused'        => 'glass-badge-warning',
                                    'not_connected' => 'glass-badge-info',
                                ];
                                $statusLabels = [
                                    'approved'      => '🟢 Approved',
                                    'pending'       => '🟡 Pending',
                                    'rejected'      => '🔴 Rejected',
                                    'paused'        => '🟠 Paused',
                                    'not_connected' => '⚫ Not Connected',
                                ];
                            @endphp
                            <span class="glass-badge {{ $statusColors[$msg->meta_template_status] ?? 'glass-badge-info' }}">
                                {{ $statusLabels[$msg->meta_template_status] ?? $msg->meta_template_status }}
                            </span>
                        </td>
                        <td class="text-xs text-gray-500">
                            {{ count($msg->buttons ?? []) }} btn(s)
                        </td>
                        <td>
                            <button wire:click="edit({{ $msg->id }})" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium bg-white/50 px-2 py-1 rounded-lg backdrop-blur-sm transition">
                                ✏️ Edit
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-gray-400">No messages found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
