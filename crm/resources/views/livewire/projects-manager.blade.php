<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">🏢 Real Estate Projects</h1>
            <p class="text-sm text-gray-500 mt-1">All 8 pre-configured Green Hill Group projects and their configurations.</p>
        </div>

        @if($successMsg)
        <div class="mb-4 bg-green-100/60 border border-green-200/60 backdrop-blur-sm rounded-lg p-3 text-green-800 text-sm font-medium">
            {{ $successMsg }}
        </div>
        @endif

        <!-- Edit Project Form -->
        @if($editingProjectId)
        <div class="mb-6 bg-yellow-100/60 border border-yellow-200/60 backdrop-blur-sm rounded-xl p-6">
            <h2 class="text-lg font-bold text-yellow-900 mb-4">✏️ Editing Project</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Project Name</label>
                    <input type="text" wire:model="projName" class="glass-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location <span class="text-gray-400">(optional)</span></label>
                    <input type="text" wire:model="projLocation" class="glass-input" placeholder="e.g. Siliguri, North Bengal">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-gray-400">(optional)</span></label>
                    <textarea wire:model="projDescription" rows="3" class="glass-input"></textarea>
                </div>
                <div>
                    <label class="flex items-center mt-2">
                        <input type="checkbox" wire:model="projActive" class="rounded border-gray-300 text-green-600">
                        <span class="ml-2 text-sm">Active</span>
                    </label>
                </div>
            </div>
            <div class="mt-4 flex gap-3">
                <button wire:click="saveProject" class="glass-button-primary !from-yellow-500 !to-amber-600">💾 Save Project</button>
                <button wire:click="cancelEdit" class="glass-button">Cancel</button>
            </div>
        </div>
        @endif

        <!-- Edit Config Form -->
        @if($editingConfigId)
        <div class="mb-6 bg-green-100/60 border border-green-200/60 backdrop-blur-sm rounded-xl p-6">
            <h2 class="text-lg font-bold text-green-900 mb-4">✏️ Editing Configuration</h2>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Configuration Name</label>
                    <input type="text" wire:model="cfgName" class="glass-input" placeholder="e.g. 2 BHK">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price Label</label>
                    <input type="text" wire:model="cfgPrice" class="glass-input" placeholder="e.g. ₹40–45 Lakh">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price Type</label>
                    <input type="text" wire:model="cfgPriceType" class="glass-input">
                </div>
            </div>
            <div class="mt-4 flex gap-3">
                <button wire:click="saveConfig" class="glass-button-primary">💾 Save Config</button>
                <button wire:click="cancelEdit" class="glass-button">Cancel</button>
            </div>
        </div>
        @endif

        <!-- Projects Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($projects as $project)
            <div class="glass-panel overflow-hidden">
                <div class="p-5 border-b border-white/50 flex justify-between items-center bg-white/30 backdrop-blur-sm">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">🏡 {{ $project->name }}</h2>
                        @if($project->location)
                        <p class="text-xs text-gray-500 mt-0.5">📍 {{ $project->location }}</p>
                        @endif
                        @if($project->description)
                        <p class="text-xs text-gray-600 mt-1">{{ $project->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="glass-badge {{ $project->is_active ? 'glass-badge-success' : 'glass-badge-warning' }}">
                            {{ $project->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <button wire:click="editProject({{ $project->id }})" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium bg-white/50 px-2 py-1 rounded-lg backdrop-blur-sm transition">✏️ Edit</button>
                    </div>
                </div>

                <div class="p-4">
                    <h3 class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3">Configurations</h3>
                    <div class="glass-table-container">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Configuration</th>
                                    <th>Price</th>
                                    <th>Type</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($project->configs as $config)
                                <tr>
                                    <td class="font-medium text-gray-800">{{ $config->config_name }}</td>
                                    <td class="text-green-700 font-semibold">{{ $config->price_label }}</td>
                                    <td class="text-gray-600">{{ $config->price_type }}</td>
                                    <td>
                                        <button wire:click="editConfig({{ $config->id }})" class="text-xs text-indigo-600 hover:text-indigo-800 bg-white/50 px-2 py-1 rounded-lg backdrop-blur-sm transition">✏️</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
