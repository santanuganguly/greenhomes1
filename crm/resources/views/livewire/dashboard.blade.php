<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        <!-- Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            
            <!-- Total Leads -->
            <div class="glass-panel p-6 flex items-center justify-between hover:scale-105 transition-transform duration-300">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Leads</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ number_format($stats['total_leads']) }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
            </div>

            <!-- Total Conversations -->
            <div class="glass-panel p-6 flex items-center justify-between hover:scale-105 transition-transform duration-300">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Conversations</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ number_format($stats['total_conversations']) }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                    </svg>
                </div>
            </div>

            <!-- Unread Messages -->
            <div class="glass-panel p-6 flex items-center justify-between hover:scale-105 transition-transform duration-300">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Needs Reply</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ number_format($stats['unread_conversations']) }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                </div>
            </div>

            <!-- Total Messages -->
            <div class="glass-panel p-6 flex items-center justify-between hover:scale-105 transition-transform duration-300">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Messages</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ number_format($stats['total_messages']) }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-rose-400 to-red-500 rounded-2xl shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Recent Leads Table -->
            <div class="lg:col-span-2 glass-panel p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-800">Recent Leads</h3>
                    <a href="{{ route('leads') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View All &rarr;</a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/40">
                                <th class="pb-3 text-sm font-semibold text-gray-600">Customer</th>
                                <th class="pb-3 text-sm font-semibold text-gray-600">Phone</th>
                                <th class="pb-3 text-sm font-semibold text-gray-600">Project</th>
                                <th class="pb-3 text-sm font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/20">
                            @forelse($recentLeads as $lead)
                                <tr class="hover:bg-white/30 transition-colors">
                                    <td class="py-3 font-medium text-gray-800">{{ $lead->customer_name ?: 'Unknown' }}</td>
                                    <td class="py-3 text-gray-600">{{ $lead->phone_number }}</td>
                                    <td class="py-3 text-gray-600">{{ $lead->project ?: '-' }}</td>
                                    <td class="py-3">
                                        @if($lead->human_requested)
                                            <span class="glass-badge glass-badge-warning text-xs">Human Req</span>
                                        @else
                                            <span class="glass-badge glass-badge-success text-xs">Bot</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-gray-500">No leads found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Activity / Inbox Sidebar -->
            <div class="glass-panel p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-800">Recent Chats</h3>
                    <a href="{{ route('inbox') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Open Inbox &rarr;</a>
                </div>
                
                <div class="space-y-4">
                    @forelse($recentConversations as $conv)
                        <a href="{{ route('inbox') }}" class="block p-4 rounded-xl bg-white/40 hover:bg-white/60 transition-colors border border-white/50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold text-gray-800">{{ $conv->customer_name ?: $conv->phone_number }}</h4>
                                    <p class="text-xs text-gray-500 mt-1 truncate max-w-[200px]">{{ $conv->last_message ?: 'New conversation' }}</p>
                                </div>
                                @if($conv->unread_count > 0)
                                    <span class="bg-indigo-600 text-white text-[10px] px-2 py-0.5 rounded-full shadow-sm">{{ $conv->unread_count }} new</span>
                                @endif
                            </div>
                            <div class="mt-2 text-[10px] text-gray-400 font-medium uppercase tracking-wider">
                                {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans() : '' }}
                            </div>
                        </a>
                    @empty
                        <div class="text-center text-gray-500 py-6">No recent conversations.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
