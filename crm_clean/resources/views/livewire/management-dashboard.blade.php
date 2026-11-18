<div class="py-6 min-h-screen text-[#a9c43d] font-mono">
    <x-crm-access-transition />
    <div class="max-w-[1400px] mx-auto sm:px-6 lg:px-8 space-y-6">

        <!-- HEADER & DATE FILTER -->
        <div class="flex flex-wrap justify-between items-end border border-[#a9c43d] p-4 bg-[#0a0a0a]">
            <div>
                <h2 class="text-2xl font-bold uppercase tracking-widest text-white">SYS_MGMT_DASHBOARD</h2>
                <p class="text-xs text-[#5f7022]">Business Performance & Analytics Engine</p>
            </div>
            
            <div class="flex items-center gap-4 mt-4 sm:mt-0">
                <div class="flex flex-col">
                    <label class="text-[10px] text-[#5f7022] uppercase">Date Range</label>
                    <select wire:model.live="dateFilter" class="bg-black border-[#a9c43d] text-[#a9c43d] focus:border-white focus:ring-0 text-sm py-1">
                        <option value="today">Today</option>
                        <option value="7_days">Last 7 Days</option>
                        <option value="30_days">Last 30 Days</option>
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                @if($dateFilter === 'custom')
                    <div class="flex flex-col">
                        <label class="text-[10px] text-[#5f7022] uppercase">Start Date</label>
                        <input type="date" wire:model.live="customStartDate" class="bg-black border-[#a9c43d] text-[#a9c43d] focus:border-white focus:ring-0 text-sm py-1">
                    </div>
                    <div class="flex flex-col">
                        <label class="text-[10px] text-[#5f7022] uppercase">End Date</label>
                        <input type="date" wire:model.live="customEndDate" class="bg-black border-[#a9c43d] text-[#a9c43d] focus:border-white focus:ring-0 text-sm py-1">
                    </div>
                @endif
            </div>
        </div>

        <!-- TOP KPIs GRID -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div class="border border-[#3d4a13] bg-[#050505] p-4 relative overflow-hidden">
                <div class="text-[10px] text-[#5f7022] mb-1">LIFETIME LEADS</div>
                <div class="text-2xl text-white font-bold">{{ number_format($kpis['total_lifetime_leads']) }}</div>
            </div>
            <div class="border border-[#a9c43d] bg-[#0a0a0a] p-4 relative overflow-hidden shadow-[0_0_15px_rgba(169,196,61,0.1)]">
                <div class="text-[10px] text-[#a9c43d] mb-1">PERIOD LEADS</div>
                <div class="text-3xl text-white font-bold">{{ number_format($kpis['period_leads']) }}</div>
            </div>
            <div class="border border-red-900 bg-[#1a0505] p-4 relative overflow-hidden">
                <div class="text-[10px] text-red-500 mb-1">HOT LEADS</div>
                <div class="text-2xl text-red-400 font-bold">{{ number_format($kpis['hot_leads']) }}</div>
            </div>
            <div class="border border-[#3d4a13] bg-[#050505] p-4 relative overflow-hidden">
                <div class="text-[10px] text-[#5f7022] mb-1">QUALIFIED</div>
                <div class="text-2xl text-white font-bold">{{ number_format($kpis['qualified']) }}</div>
            </div>
            <div class="border border-[#3d4a13] bg-[#050505] p-4 relative overflow-hidden">
                <div class="text-[10px] text-[#5f7022] mb-1">SITE VISITS</div>
                <div class="text-2xl text-white font-bold">{{ number_format($kpis['site_visits']) }}</div>
            </div>
            <div class="border border-[#3d4a13] bg-[#050505] p-4 relative overflow-hidden">
                <div class="text-[10px] text-[#5f7022] mb-1">NEGOTIATIONS</div>
                <div class="text-2xl text-white font-bold">{{ number_format($kpis['negotiations']) }}</div>
            </div>
            <div class="border border-yellow-900 bg-[#1a1505] p-4 relative overflow-hidden">
                <div class="text-[10px] text-yellow-500 mb-1">BOOKINGS</div>
                <div class="text-2xl text-yellow-400 font-bold">{{ number_format($kpis['bookings']) }}</div>
            </div>
            <div class="border border-green-700 bg-[#051a0a] p-4 relative overflow-hidden">
                <div class="text-[10px] text-green-500 mb-1">CONVERTED</div>
                <div class="text-2xl text-green-400 font-bold">{{ number_format($kpis['converted']) }}</div>
            </div>
            <div class="border border-green-500 bg-[#0a2a10] p-4 relative overflow-hidden col-span-2 lg:col-span-2">
                <div class="text-[10px] text-green-400 mb-1">SALES VALUE (₹)</div>
                <div class="text-3xl text-green-300 font-bold">₹ {{ number_format($kpis['sales_value'], 2) }}</div>
            </div>
        </div>

        <!-- FUNNEL & SALES PERFORMANCE -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- SALES FUNNEL -->
            <div class="border border-[#a9c43d] bg-[#0a0a0a] p-4">
                <h3 class="border-b border-[#3d4a13] pb-2 mb-4 text-white uppercase font-bold text-sm">Sales Funnel</h3>
                
                <div class="space-y-2 text-sm">
                    @php 
                        $total = $funnel['Leads'] > 0 ? $funnel['Leads'] : 1; 
                        $stagesOrder = ['Leads', 'Contacted', 'Qualified', 'Site Visit', 'Negotiation', 'Booking', 'Converted'];
                    @endphp

                    @foreach($stagesOrder as $index => $stage)
                        @php
                            $count = $funnel[$stage];
                            $width = max(10, ($count / $total) * 100);
                            $pct = round(($count / $total) * 100, 1);
                            
                            $dropoff = '';
                            if ($index > 0) {
                                $prevCount = $funnel[$stagesOrder[$index - 1]];
                                if ($prevCount > 0) {
                                    $convFromPrev = round(($count / $prevCount) * 100, 1);
                                    $dropoff = $convFromPrev . '% from prev';
                                }
                            }
                        @endphp
                        
                        <div class="relative pt-1">
                            <div class="flex justify-between items-end mb-1 text-xs">
                                <div>
                                    <span class="text-white">{{ $stage }}</span> 
                                    <span class="text-[#5f7022]">({{ number_format($count) }})</span>
                                </div>
                                <div class="text-[#a9c43d]">{{ $pct }}%</div>
                            </div>
                            <div class="w-full bg-[#111] h-6 flex items-center">
                                <div class="bg-[#a9c43d] h-6 flex items-center px-2 text-black text-[10px] font-bold overflow-hidden whitespace-nowrap transition-all duration-500" style="width: {{ $width }}%">
                                </div>
                            </div>
                            @if($dropoff)
                                <div class="text-right text-[9px] text-[#5f7022] mt-0.5">{{ $dropoff }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- SALES PERFORMANCE -->
            <div class="border border-[#a9c43d] bg-[#0a0a0a] p-4 lg:col-span-2">
                <h3 class="border-b border-[#3d4a13] pb-2 mb-4 text-white uppercase font-bold text-sm">Sales Performance (Per Agent)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="text-[#5f7022] uppercase border-b border-[#3d4a13]">
                            <tr>
                                <th class="py-2">Agent</th>
                                <th class="py-2 text-center">Leads</th>
                                <th class="py-2 text-center">Qual.</th>
                                <th class="py-2 text-center">Visits</th>
                                <th class="py-2 text-center">Bkg.</th>
                                <th class="py-2 text-center text-green-500">Conv.</th>
                                <th class="py-2 text-right text-green-500">Sales Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($salesPerformance as $perf)
                                @php
                                    $rate = $perf->total_leads > 0 ? round(($perf->converted / $perf->total_leads) * 100, 1) : 0;
                                @endphp
                                <tr class="border-b border-[#111] hover:bg-[#111]">
                                    <td class="py-3 text-white">{{ $perf->assignedUser->name ?? 'Unassigned' }}</td>
                                    <td class="py-3 text-center">{{ $perf->total_leads }}</td>
                                    <td class="py-3 text-center">{{ $perf->qualified }}</td>
                                    <td class="py-3 text-center">{{ $perf->site_visits }}</td>
                                    <td class="py-3 text-center text-yellow-500">{{ $perf->bookings }}</td>
                                    <td class="py-3 text-center text-green-400 font-bold">{{ $perf->converted }} ({{ $rate }}%)</td>
                                    <td class="py-3 text-right text-green-400">₹{{ number_format($perf->sales_value) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-[#5f7022]">No performance data in this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3-COL GRID: SOURCES, HEALTH, LOST -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- LEAD SOURCES -->
            <div class="border border-[#3d4a13] bg-[#050505] p-4">
                <h3 class="border-b border-[#3d4a13] pb-2 mb-4 text-[#a9c43d] uppercase font-bold text-sm">Lead Sources</h3>
                <div class="space-y-3">
                    @forelse($leadSources as $src)
                        <div class="flex justify-between items-center text-sm border-b border-[#111] pb-2">
                            <span class="text-white">{{ $src->lead_source ?: 'Unknown / Bot' }}</span>
                            <span class="bg-[#111] px-2 py-0.5 rounded text-[#a9c43d]">{{ number_format($src->total) }}</span>
                        </div>
                    @empty
                        <div class="text-[#5f7022] text-xs">No sources logged.</div>
                    @endforelse
                </div>
            </div>

            <!-- FOLLOW-UP HEALTH -->
            <div class="border border-[#3d4a13] bg-[#050505] p-4">
                <h3 class="border-b border-[#3d4a13] pb-2 mb-4 text-[#a9c43d] uppercase font-bold text-sm">Follow-up Health</h3>
                
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div class="bg-[#111] p-3 border border-[#3d4a13]">
                        <div class="text-xs text-[#5f7022] mb-1">TODAY</div>
                        <div class="text-xl text-white">{{ $followUpHealth['today'] }}</div>
                    </div>
                    <div class="bg-[#1a0505] p-3 border border-red-900">
                        <div class="text-xs text-red-500 mb-1">OVERDUE</div>
                        <div class="text-xl text-red-400 font-bold">{{ $followUpHealth['overdue'] }}</div>
                    </div>
                    <div class="bg-[#111] p-3 border border-[#3d4a13]">
                        <div class="text-xs text-[#5f7022] mb-1">UNCONTACTED</div>
                        <div class="text-xl text-white">{{ $followUpHealth['uncontacted'] }}</div>
                    </div>
                    <div class="bg-[#1a0505] p-3 border border-red-900">
                        <div class="text-xs text-red-500 mb-1">HOT (NO FLWUP)</div>
                        <div class="text-xl text-red-400 font-bold">{{ $followUpHealth['hot_no_followup'] }}</div>
                    </div>
                </div>
            </div>

            <!-- LOST REASONS -->
            <div class="border border-[#3d4a13] bg-[#050505] p-4">
                <h3 class="border-b border-[#3d4a13] pb-2 mb-4 text-red-500 uppercase font-bold text-sm">Lost Reasons</h3>
                <div class="space-y-3">
                    @forelse($lostReasons as $lost)
                        <div class="flex justify-between items-center text-sm border-b border-[#111] pb-2">
                            <span class="text-gray-400">{{ $lost->lost_reason ?: 'Unspecified' }}</span>
                            <span class="bg-[#1a0505] px-2 py-0.5 text-red-500">{{ number_format($lost->total) }}</span>
                        </div>
                    @empty
                        <div class="text-[#5f7022] text-xs">No lost leads in period.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- SECONDARY: TECHNICAL TELEMETRY -->
        <div class="mt-12 border border-[#111] bg-black p-4">
            <h3 class="text-xs text-gray-600 uppercase font-bold mb-4 tracking-wider">> TECHNICAL_TELEMETRY</h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs text-gray-500">
                <div>
                    <span class="block">WHATSAPP_ENGINE:</span>
                    <span class="text-green-600 font-bold animate-pulse">ONLINE</span>
                </div>
                <div>
                    <span class="block">ACTIVE_CONFIGS:</span>
                    <span class="text-gray-400">{{ $telemetry['active_projects'] }}</span>
                </div>
                <div>
                    <span class="block">IN_CONVERSATIONS:</span>
                    <span class="text-gray-400">{{ number_format($telemetry['total_conversations']) }}</span>
                </div>
                <div>
                    <span class="block">MSG_PROCESSED:</span>
                    <span class="text-gray-400">{{ number_format($telemetry['total_messages']) }}</span>
                </div>
            </div>
        </div>

    </div>
</div>
