<div class="py-12" wire:poll="loadMessages">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="glass-panel overflow-hidden flex h-[70vh]">
            
            <!-- Sidebar / Conversations List -->
            <div class="w-1/3 border-r border-white/50 overflow-y-auto bg-white/20">
                <div class="p-4 bg-white/30 backdrop-blur-md border-b border-white/50 font-semibold text-gray-800">
                    Conversations
                </div>
                
                @forelse($conversations as $conv)
                    <div wire:click="selectConversation({{ $conv->id }})" 
                         class="p-4 border-b border-white/30 cursor-pointer hover:bg-white/40 transition-colors {{ $activeConversationId === $conv->id ? 'bg-white/50' : '' }}">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-gray-800">{{ $conv->customer_name ?: $conv->phone_number }}</span>
                            @if($conv->unread_count > 0)
                                <span class="bg-green-600 text-white text-xs px-2 py-1 rounded-full">{{ $conv->unread_count }}</span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500 mt-1 truncate">
                            {{ $conv->last_message ?: 'New Conversation' }}
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-xs text-gray-400">
                                {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans() : '' }}
                            </span>
                            <span class="text-xs px-2 py-0.5 glass-badge {{ $conv->status === 'bot' ? 'glass-badge-success' : 'glass-badge-warning' }}">
                                {{ ucfirst($conv->status) }} Mode
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-gray-500 text-sm">
                        No conversations yet.
                    </div>
                @endforelse
            </div>

            <!-- Chat Area -->
            <div class="w-2/3 flex flex-col bg-white/10 backdrop-blur-sm">
                @if($activeConversationId)
                    @php
                        $activeConv = $conversations->firstWhere('id', $activeConversationId);
                    @endphp
                    
                    <!-- Chat Header -->
                    <div class="p-4 bg-white/40 backdrop-blur-md border-b border-white/50 flex justify-between items-center shadow-sm z-10">
                        <div>
                            <div class="font-bold text-gray-800">{{ $activeConv->customer_name ?: $activeConv->phone_number }}</div>
                            <div class="text-sm text-gray-600">{{ $activeConv->phone_number }}</div>
                        </div>
                        <button wire:click="toggleBotMode" class="glass-button text-sm">
                            Switch to {{ $activeConv->status === 'bot' ? 'Human' : 'Bot' }} Mode
                        </button>
                    </div>

                    <!-- Messages -->
                    <div id="chat-messages" 
                         x-data="{
                             init() {
                                 this.scrollToBottom();
                                 const observer = new MutationObserver(() => this.scrollToBottom());
                                 observer.observe(this.$el, { childList: true, subtree: true });
                             },
                             scrollToBottom() {
                                 this.$el.scrollTop = this.$el.scrollHeight;
                             }
                         }"
                         class="flex-1 p-4 overflow-y-auto flex flex-col space-y-3">
                        @forelse($chatMessages as $msg)
                            <div class="flex {{ $msg->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[70%] rounded-2xl p-3 {{ $msg->direction === 'outbound' ? 'bg-indigo-600/80 backdrop-blur-md text-white border border-indigo-400/50 rounded-br-none shadow-md' : 'bg-white/60 backdrop-blur-md text-gray-800 rounded-bl-none shadow-sm border border-white/60' }}">
                                    @if($msg->type === 'text')
                                        {{ $msg->content }}
                                    @elseif($msg->type === 'interactive' && $msg->direction === 'inbound')
                                        @php
                                            $title = $msg->media_payload['interactive']['button_reply']['title'] ?? 
                                                     $msg->media_payload['interactive']['list_reply']['title'] ?? 
                                                     $msg->content;
                                        @endphp
                                        <div class="flex items-center space-x-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 opacity-70">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243-1.59-1.59" />
                                            </svg>
                                            <span class="font-medium">{{ $title }}</span>
                                        </div>
                                    @elseif($msg->type === 'interactive' && $msg->direction === 'outbound')
                                        {{ $msg->content }}
                                        <div class="text-[10px] italic opacity-75 mt-1 border-t border-white/20 pt-1">Interactive Menu</div>
                                    @elseif($msg->type === 'template')
                                        <div class="flex items-center space-x-2 italic">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 opacity-70">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                                            </svg>
                                            <span>Template: {{ $msg->content }}</span>
                                        </div>
                                    @elseif($msg->type === 'document')
                                        <div class="flex items-center space-x-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 opacity-70">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            <span>{{ $msg->content }}</span>
                                        </div>
                                    @elseif($msg->type === 'image')
                                        <div class="flex items-center space-x-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 opacity-70">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                            </svg>
                                            <span>Image</span>
                                        </div>
                                    @else
                                        <span class="italic text-sm opacity-75">[{{ ucfirst($msg->type) }} message]</span>
                                    @endif
                                    
                                    <div class="text-[10px] mt-1 text-right {{ $msg->direction === 'outbound' ? 'text-green-100' : 'text-gray-400' }}">
                                        {{ $msg->created_at->format('g:i A') }}
                                        @if($msg->direction === 'outbound')
                                            <span class="ml-1">✓</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-gray-500 mt-10">
                                Send a message to start the conversation.
                            </div>
                        @endforelse
                    </div>

                    <!-- Input Area -->
                    <div class="p-4 bg-white/40 backdrop-blur-md border-t border-white/50">
                        <form wire:submit.prevent="sendMessage" class="flex items-center space-x-2">
                            <input type="text" wire:model="newMessage" placeholder="Type a message..." class="glass-input !rounded-full !py-2.5" required>
                            <button type="submit" class="glass-button-primary !rounded-full !p-2 h-11 w-11 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 ml-0.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                                </svg>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="flex-1 flex items-center justify-center text-gray-400 flex-col">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 mb-4 opacity-50">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                        </svg>
                        Select a conversation to start messaging
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
