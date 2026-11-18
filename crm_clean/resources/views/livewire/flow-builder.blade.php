<div class="py-8">
 <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

 <!-- Header + stats -->
 <div class="mb-6 flex items-center justify-between">
 <div>
 <h1 class="text-2xl font-bold text-gray-900">WhatsApp Automation Flows</h1>
 <p class="text-sm text-gray-500 mt-1">Pre-configured conversation flows for Green Hill Group &amp; Green Homes.</p>
 </div>
 <div class="flex gap-3">
 <a href="{{ route('automation.messages') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
 Edit Messages
 </a>
 <button wire:click="createFlow" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
 + New Flow
 </button>
 </div>
 </div>

 <div class="flex space-x-6">
 <!-- Flows List (Left Sidebar) -->
 <div class="w-1/4 bg-white overflow-hidden shadow-sm rounded-xl">
 <div class="p-4 border-b border-gray-200 bg-gray-50">
 <h2 class="font-bold text-gray-800 text-sm">7 Configured Flows</h2>
 </div>
 <div class="divide-y divide-gray-100">
 @forelse($flows as $flow)
 <div wire:click="selectFlow({{ $flow->id }})"
 class="p-3 cursor-pointer hover:bg-gray-50 {{ $activeFlowId === $flow->id ? 'bg-green-50 border-l-4 border-green-600' : '' }}">
 <div class="font-semibold text-gray-800 text-sm">{{ $flow->name }}</div>
 <div class="text-xs text-gray-500 mt-1 flex items-center gap-2">
 <span class="{{ $flow->is_active ? 'text-green-600' : 'text-gray-400' }}">
 {{ $flow->is_active ? '● Active' : '○ Paused' }}
 </span>
 @if($flow->is_default_welcome)
 <span class="bg-yellow-100 text-yellow-700 px-1.5 rounded text-xs">Default</span>
 @endif
 @if($flow->business_vertical)
 <span class="bg-green-100 text-green-700 px-1.5 rounded text-xs">{{ $flow->business_vertical }}</span>
 @endif
 </div>
 <div class="text-xs text-gray-400 mt-0.5">{{ $flow->nodes->count() }} nodes</div>
 </div>
 @empty
 <div class="p-4 text-center text-sm text-gray-500">No flows created.</div>
 @endforelse
 </div>
 </div>

 <!-- Flow Editor (Right Area) -->
 <div class="w-3/4 bg-white overflow-hidden shadow-sm rounded-xl flex flex-col">
 @if($activeFlowId)
 @php $activeFlow = $flows->firstWhere('id', $activeFlowId); @endphp
 <div class="p-5 border-b border-gray-200 bg-gray-50">
 <h2 class="text-xl font-bold text-gray-800">{{ $activeFlow->name }}</h2>
 <p class="text-sm text-gray-500 mt-1">
 Group: <span class="font-mono text-xs bg-gray-200 px-1.5 rounded">{{ $activeFlow->group_name ?? '—' }}</span>
 &nbsp;·&nbsp;
 Nodes: <strong>{{ $activeFlow->nodes->count() }}</strong>
 </p>
 </div>

 <div class="p-6 flex-1 overflow-y-auto bg-gray-50">
 <div class="space-y-4">
 @forelse($activeFlow->nodes as $node)
 @php $bm = $node->botMessage; @endphp
 <div class="bg-white p-4 rounded-xl shadow-sm border {{ $node->is_start ? 'border-green-400' : 'border-gray-200' }}">
 <div class="flex justify-between items-start mb-3">
 <div class="flex items-center gap-2">
 @if($node->is_start)
 <span class="bg-green-600 text-white text-xs px-2 py-0.5 rounded">START</span>
 @endif
 <span class="bg-gray-800 text-white text-xs px-2 py-0.5 rounded">#{{ $node->id }}</span>
 <h3 class="font-bold text-gray-800">{{ $node->name }}</h3>
 </div>
 <div class="flex items-center gap-2">
 <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $node->input_type }}</span>
 @if($node->triggers_human_handover)
 <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded">⚠️ Human Handover</span>
 @endif
 </div>
 </div>

 @if($bm)
 <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 text-sm">
 @if($bm->header_text)
 <div class="font-bold text-gray-700 text-xs uppercase tracking-wider mb-1">{{ $bm->header_text }}</div>
 @endif
 <div class="text-gray-700 whitespace-pre-wrap text-xs">{{ mb_substr($bm->body_text, 0, 200) }}{{ mb_strlen($bm->body_text) > 200 ? '...' : '' }}</div>
 @if($bm->footer_text)
 <div class="text-gray-400 text-xs mt-1 italic">{{ $bm->footer_text }}</div>
 @endif

 @if(!empty($bm->buttons))
 <div class="flex flex-wrap gap-1 mt-2">
 @foreach($bm->buttons as $btn)
 <span class="text-xs bg-green-50 border border-green-200 text-green-700 px-2 py-0.5 rounded">
 {{ $btn['label'] ?? '' }}
 </span>
 @endforeach
 </div>
 @endif

 @if(!empty($bm->list_sections))
 <div class="mt-2">
 <span class="text-xs text-gray-500">📋 List: {{ $bm->list_button_label }}</span>
 </div>
 @endif

 <div class="mt-2 flex items-center gap-2">
 @php
 $statusColors = [
 'approved' => 'bg-green-100 text-green-700',
 'pending' => 'bg-yellow-100 text-yellow-700',
 'rejected' => 'bg-red-100 text-red-700',
 'not_connected' => 'bg-gray-100 text-gray-500',
 'paused' => 'bg-orange-100 text-orange-700',
 ];
 @endphp
 @if($bm->meta_template_name)
 <span class="font-mono text-xs bg-gray-100 px-1.5 rounded text-gray-600">{{ $bm->meta_template_name }}</span>
 @endif
 <span class="text-xs px-2 py-0.5 rounded {{ $statusColors[$bm->meta_template_status] ?? 'bg-gray-100 text-gray-500' }}">
 {{ $bm->meta_template_status }}
 </span>
 <a href="{{ route('automation.messages') }}" wire:navigate class="text-xs text-green-600 hover:underline ml-2"> Edit</a>
 </div>
 </div>
 @endif

 @if($node->expected_lead_field)
 <div class="mt-2 text-xs text-green-700 bg-green-50 px-2 py-1 rounded">
 📝 Saves customer answer → lead.<strong>{{ $node->expected_lead_field }}</strong>
 </div>
 @endif

 <!-- Edges -->
 @if($node->edges->count() > 0)
 <div class="mt-3 pt-3 border-t border-gray-100">
 <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Routes</h4>
 <div class="flex flex-wrap gap-1">
 @foreach($node->edges->take(6) as $edge)
 <div class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded font-mono">
 "{{ $edge->condition_payload }}" → #{{ $edge->target_node_id }}
 @if($edge->save_value) <span class="text-green-600">({{ $edge->save_value }})</span>@endif
 </div>
 @endforeach
 @if($node->edges->count() > 6)
 <div class="text-xs text-gray-400">+{{ $node->edges->count() - 6 }} more...</div>
 @endif
 </div>
 </div>
 @endif
 </div>
 @empty
 <div class="text-center text-gray-400 py-10">No nodes added to this flow yet.</div>
 @endforelse
 </div>

 <!-- Add Node Form -->
 <div class="mt-8 bg-white p-5 rounded-xl shadow-sm border border-green-200">
 <h3 class="font-bold text-base mb-4 text-gray-800">Add New Node</h3>
 <form wire:submit.prevent="addNode" class="space-y-4">
 <div class="grid grid-cols-2 gap-4">
 <div>
 <label class="block text-sm font-medium text-gray-700">Node Name</label>
 <input type="text" wire:model="nodeName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
 </div>
 <div>
 <label class="block text-sm font-medium text-gray-700">Message Type</label>
 <select wire:model="nodeType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
 <option value="template">Meta Template</option>
 <option value="text">Bot Message / Text</option>
 <option value="action">Action Only</option>
 </select>
 </div>
 <div>
 <label class="block text-sm font-medium text-gray-700">Expected Lead Field</label>
 <select wire:model="nodeExpectedLeadField" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
 <option value="">-- None --</option>
 <option value="customer_name">Name</option>
 <option value="business_vertical">Business Vertical</option>
 <option value="project">Project</option>
 <option value="configuration">Configuration</option>
 <option value="owns_land">Owns Land?</option>
 <option value="home_type">Home Type</option>
 <option value="floors">Floors</option>
 <option value="builtup_area">Built-up Area</option>
 <option value="budget">Budget</option>
 <option value="timeline">Timeline</option>
 <option value="land_location">Land Location</option>
 <option value="plot_size">Plot Size</option>
 <option value="site_visit_date">Site Visit Date</option>
 <option value="site_visit_time">Site Visit Time</option>
 </select>
 </div>
 </div>
 @if($nodeType === 'template')
 <div>
 <label class="block text-sm font-medium text-gray-700">Meta Template Name</label>
 <input type="text" wire:model="nodeTemplateName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
 </div>
 @elseif($nodeType === 'text')
 <div>
 <label class="block text-sm font-medium text-gray-700">Text Content</label>
 <textarea wire:model="nodeTextContent" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"></textarea>
 </div>
 @endif
 <div class="flex items-center">
 <input id="handover" type="checkbox" wire:model="nodeTriggersHumanHandover" class="h-4 w-4 rounded border-gray-300 text-green-600">
 <label for="handover" class="ml-2 text-sm text-gray-900">Trigger Human Handover</label>
 </div>
 <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-lg text-sm font-medium hover:bg-green-700">
 Save Node
 </button>
 </form>
 </div>
 </div>
 @else
 <div class="flex-1 flex items-center justify-center text-gray-400">
 <div class="text-center">
 <h3 class="text-sm font-medium text-gray-900">No flow selected</h3>
 <p class="mt-1 text-sm text-gray-500">Select a flow from the left to view its configuration.</p>
 </div>
 </div>
 @endif
 </div>
 </div>
 </div>
</div>
