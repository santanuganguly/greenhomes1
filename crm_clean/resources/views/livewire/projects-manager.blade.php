<div class="py-8">
 <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

 <div class="mb-6">
 <h1 class="text-2xl font-bold text-gray-900"> Real Estate Projects</h1>
 <p class="text-sm text-gray-500 mt-1">All 8 pre-configured Green Hill Group projects and their configurations.</p>
 </div>

 @if($successMsg)
 <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-3 text-green-800 text-sm font-medium">
 {{ $successMsg }}
 </div>
 @endif

 <!-- Edit Project Form -->
 @if($editingProjectId)
 <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-xl p-6">
 <h2 class="text-lg font-bold text-yellow-900 mb-4"> Editing Project</h2>
 <div class="grid grid-cols-2 gap-4">
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Project Name</label>
 <input type="text" wire:model="projName" class="w-full rounded border-gray-300 text-sm shadow-sm">
 </div>
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Location <span class="text-gray-400">(optional)</span></label>
 <input type="text" wire:model="projLocation" class="w-full rounded border-gray-300 text-sm shadow-sm" placeholder="e.g. Siliguri, North Bengal">
 </div>
 <div class="col-span-2">
 <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-gray-400">(optional)</span></label>
 <textarea wire:model="projDescription" rows="3" class="w-full rounded border-gray-300 text-sm shadow-sm"></textarea>
 </div>
 <div>
 <label class="flex items-center mt-2">
 <input type="checkbox" wire:model="projActive" class="rounded border-gray-300 text-green-600">
 <span class="ml-2 text-sm">Active</span>
 </label>
 </div>
 </div>
 <div class="mt-4 flex gap-3">
 <button wire:click="saveProject" class="bg-yellow-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-yellow-700"> Save Project</button>
 <button wire:click="cancelEdit" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
 </div>
 </div>
 @endif

 <!-- Edit Config Form -->
 @if($editingConfigId)
 <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-6">
 <h2 class="text-lg font-bold text-green-900 mb-4"> Editing Configuration</h2>
 <div class="grid grid-cols-3 gap-4">
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Configuration Name</label>
 <input type="text" wire:model="cfgName" class="w-full rounded border-gray-300 text-sm shadow-sm" placeholder="e.g. 2 BHK">
 </div>
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Price Label</label>
 <input type="text" wire:model="cfgPrice" class="w-full rounded border-gray-300 text-sm shadow-sm" placeholder="e.g. ₹40–45 Lakh">
 </div>
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Price Type</label>
 <input type="text" wire:model="cfgPriceType" class="w-full rounded border-gray-300 text-sm shadow-sm">
 </div>
 </div>
 <div class="mt-4 flex gap-3">
 <button wire:click="saveConfig" class="bg-green-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-green-700"> Save Config</button>
 <button wire:click="cancelEdit" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
 </div>
 </div>
 @endif

 <!-- Projects Grid -->
 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
 @foreach($projects as $project)
 <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
 <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
 <div>
 <h2 class="text-lg font-bold text-gray-900"> {{ $project->name }}</h2>
 @if($project->location)
 <p class="text-xs text-gray-500 mt-0.5"> {{ $project->location }}</p>
 @endif
 @if($project->description)
 <p class="text-xs text-gray-600 mt-1">{{ $project->description }}</p>
 @endif
 </div>
 <div class="flex items-center gap-2">
 <span class="text-xs px-2 py-0.5 rounded {{ $project->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
 {{ $project->is_active ? 'Active' : 'Inactive' }}
 </span>
 <button wire:click="editProject({{ $project->id }})" class="text-xs text-green-600 hover:text-green-800 font-medium"> Edit</button>
 </div>
 </div>

 <div class="p-4">
 <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Configurations</h3>
 <table class="min-w-full text-sm">
 <thead>
 <tr class="text-xs text-gray-500">
 <th class="text-left pb-2">Configuration</th>
 <th class="text-left pb-2">Price</th>
 <th class="text-left pb-2">Type</th>
 <th class="text-left pb-2"></th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-50">
 @foreach($project->configs as $config)
 <tr>
 <td class="py-2 font-medium text-gray-800">{{ $config->config_name }}</td>
 <td class="py-2 text-green-700 font-semibold">{{ $config->price_label }}</td>
 <td class="py-2 text-xs text-gray-400">{{ $config->price_type }}</td>
 <td class="py-2">
 <button wire:click="editConfig({{ $config->id }})" class="text-xs text-green-600 hover:text-green-800"></button>
 </td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 </div>
 @endforeach
 </div>
 </div>
</div>
