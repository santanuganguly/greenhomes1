<div class="py-6 min-h-screen text-[#a9c43d]" x-data="leadPipeline()">
    <div class="max-w-[1400px] mx-auto sm:px-6 lg:px-8">
        
        <!-- Funnel Summary Header -->
        <div class="mb-6 border border-[#a9c43d] p-4 bg-[#0a0a0a]">
            <h2 class="text-lg font-bold mb-4 uppercase border-b border-[#a9c43d] pb-2">Admin Funnel Summary</h2>
            <div class="flex flex-wrap items-center justify-between text-sm font-mono gap-y-4">
                <div class="flex items-center gap-2">
                    <span class="text-[#5f7022]">TOTAL LEADS:</span> 
                    <span class="font-bold text-white">{{ number_format($funnel['total_leads']) }}</span>
                </div>
                
                @foreach(['Contacted', 'Qualified', 'Site Visit Completed', 'Negotiation', 'Booking'] as $stg)
                    <div class="text-[#5f7022] hidden md:block">→</div>
                    <div class="flex flex-col items-center">
                        <span class="text-[#5f7022] text-xs uppercase">{{ $stg }}</span>
                        <span class="font-bold">{{ number_format($funnel['totals'][$stg] ?? 0) }}</span>
                    </div>
                @endforeach
                
                <div class="text-[#5f7022] hidden md:block">→</div>
                
                <div class="flex flex-col items-center border border-[#a9c43d] px-3 py-1">
                    <span class="text-[#5f7022] text-xs uppercase">CONVERTED</span>
                    <span class="font-bold text-white">{{ number_format($funnel['converted']) }}</span>
                </div>
                
                <div class="flex items-center gap-2 ml-4">
                    <span class="text-[#5f7022]">CONV. RATE:</span> 
                    <span class="font-bold {{ $funnel['conversion_rate'] > 0 ? 'text-green-400' : '' }}">{{ $funnel['conversion_rate'] }}%</span>
                </div>
                
                <div class="flex items-center gap-2 ml-4">
                    <span class="text-[#5f7022]">LOST:</span> 
                    <span class="font-bold text-red-500">{{ number_format($funnel['lost']) }}</span>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="mb-6 border border-[#a9c43d] p-4 bg-[#0a0a0a] flex flex-wrap gap-4 items-center">
            <div class="flex-grow min-w-[200px]">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="> Search Name, Phone, Project..." 
                    class="w-full bg-black border-[#a9c43d] text-[#a9c43d] focus:border-white focus:ring-0 placeholder-[#5f7022] font-mono text-sm py-1">
            </div>
            <div class="w-40">
                <select wire:model.live="filterTemperature" class="w-full bg-black border-[#a9c43d] text-[#a9c43d] focus:border-white focus:ring-0 font-mono text-sm py-1">
                    <option value="">[All Temps]</option>
                    <option value="Hot">Hot</option>
                    <option value="Warm">Warm</option>
                    <option value="Cold">Cold</option>
                </select>
            </div>
            <div class="w-48">
                <select wire:model.live="filterProject" class="w-full bg-black border-[#a9c43d] text-[#a9c43d] focus:border-white focus:ring-0 font-mono text-sm py-1">
                    <option value="">[All Projects]</option>
                    <!-- Ideally populate dynamically, hardcoded for demo or user types it -->
                    <option value="Green Homes">Green Homes</option>
                    <option value="Green Hill Group">Green Hill Group</option>
                </select>
            </div>
            <div class="w-40">
                <select wire:model.live="filterSource" class="w-full bg-black border-[#a9c43d] text-[#a9c43d] focus:border-white focus:ring-0 font-mono text-sm py-1">
                    <option value="">[All Sources]</option>
                    <option value="WhatsApp Bot">WhatsApp Bot</option>
                    <option value="Manual Entry">Manual Entry</option>
                    <option value="Bulk Campaign">Bulk Campaign</option>
                </select>
            </div>
        </div>

        <!-- Kanban Pipeline -->
        <div class="flex overflow-x-auto gap-4 pb-8 snap-x" style="height: calc(100vh - 300px); min-height: 500px;">
            @foreach($stages as $stage)
                <div class="flex-shrink-0 w-80 flex flex-col border border-[#5f7022] bg-[#050505] snap-start"
                     @dragover.prevent="dragOverStage = '{{ $stage }}'"
                     @dragleave.prevent="dragOverStage = null"
                     @drop.prevent="dropLead('{{ $stage }}')"
                     :class="dragOverStage === '{{ $stage }}' ? 'border-[#a9c43d] bg-[#0a0a0a]' : ''">
                    
                    <!-- Stage Header -->
                    <div class="p-3 border-b border-[#5f7022] bg-[#0a0a0a] flex justify-between items-center sticky top-0">
                        <h3 class="font-bold uppercase text-sm">{{ $stage }}</h3>
                        <span class="text-xs bg-[#a9c43d] text-black px-2 py-0.5 font-bold">{{ count($leadsByStage[$stage]) }}</span>
                    </div>

                    <!-- Leads List (Droppable Area) -->
                    <div class="p-2 flex-grow overflow-y-auto flex flex-col gap-2 custom-scrollbar">
                        @foreach($leadsByStage[$stage] as $lead)
                            <!-- Lead Card -->
                            <div class="border border-[#3d4a13] bg-black p-3 cursor-pointer hover:border-[#a9c43d] transition-colors"
                                 draggable="true"
                                 @dragstart="dragStart('{{ $lead->id }}', '{{ $stage }}')"
                                 @dragend="dragEnd()"
                                 wire:key="lead-{{ $lead->id }}"
                                 onclick="window.location.href='{{ route('inbox') }}?conversation={{ $lead->conversation_id }}'">
                                
                                <div class="flex justify-between items-start mb-2">
                                    <div class="font-bold text-white truncate text-sm" title="{{ $lead->customer_name }}">
                                        {{ $lead->customer_name ?: 'UNKNOWN_CONTACT' }}
                                    </div>
                                    @if($lead->temperature == 'Hot')
                                        <span class="text-xs text-red-500 font-bold border border-red-900 px-1">HOT</span>
                                    @elseif($lead->temperature == 'Warm')
                                        <span class="text-xs text-yellow-500 font-bold border border-yellow-900 px-1">WARM</span>
                                    @endif
                                </div>
                                
                                <div class="text-xs text-[#5f7022] mb-2 font-mono">
                                    <div>PH: {{ $lead->phone_number }}</div>
                                    @if($lead->project) <div>PRJ: {{ $lead->project }}</div> @endif
                                </div>

                                <div class="flex justify-between items-end mt-3 pt-2 border-t border-[#3d4a13] text-[10px] uppercase text-[#5f7022]">
                                    <div>
                                        SRC: {{ $lead->lead_source ?: 'BOT' }}
                                    </div>
                                    <div>
                                        LST: {{ $lead->last_contact_date ? $lead->last_contact_date->format('M d') : 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('leadPipeline', () => ({
                draggedLeadId: null,
                sourceStage: null,
                dragOverStage: null,

                dragStart(id, stage) {
                    this.draggedLeadId = id;
                    this.sourceStage = stage;
                },
                
                dragEnd() {
                    this.draggedLeadId = null;
                    this.sourceStage = null;
                    this.dragOverStage = null;
                },

                dropLead(targetStage) {
                    if (this.draggedLeadId && this.sourceStage !== targetStage) {
                        @this.call('updateLeadStage', this.draggedLeadId, targetStage);
                    }
                    this.dragEnd();
                }
            }))
        })
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #050505; 
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #3d4a13; 
        }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb {
            background: #5f7022; 
        }
    </style>
</div>
