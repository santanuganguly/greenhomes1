<div class="h-[calc(100vh-4rem)] bg-gray-100 flex overflow-hidden font-sans" wire:poll="refreshData">
 <!-- Sidebar / Conversations List -->
 <div class="w-full md:w-1/3 lg:w-1/4 bg-white border-r border-gray-200 flex flex-col h-full">
 <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center shadow-sm z-10">
 <h2 class="font-bold text-gray-800 text-lg tracking-tight">Conversations</h2>
 </div>
 
 <div class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300">
 @forelse($conversations as $conv)
 <div wire:click="selectConversation({{ $conv->id }})" 
 class="p-4 border-b border-gray-100 cursor-pointer transition duration-150 ease-in-out hover:bg-gray-50 relative {{ $activeConversationId === $conv->id ? 'bg-indigo-50 border-l-4 border-l-indigo-500' : 'border-l-4 border-l-transparent' }}">
 <div class="flex justify-between items-start mb-1">
 <div class="font-semibold text-gray-900 truncate pr-2">
 {{ $conv->customer_name ?: $conv->phone_number }}
 </div>
 @if($conv->unread_count > 0)
 <span class="bg-indigo-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">{{ $conv->unread_count }}</span>
 @endif
 </div>
 <div class="text-sm text-gray-500 truncate mb-2">
 {{ $conv->last_message ?: 'New Conversation' }}
 </div>
 <div class="flex justify-between items-center">
 <span class="text-xs text-gray-400 font-medium">
 {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans() : '' }}
 </span>
 <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full {{ $conv->status === 'bot' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
 {{ $conv->status }}
 </span>
 </div>
 </div>
 @empty
 <div class="p-8 flex flex-col items-center justify-center h-full text-gray-400">
 <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
 <p class="text-sm">No conversations yet.</p>
 </div>
 @endforelse
 </div>
 </div>

 <!-- Chat Area -->
 <div class="w-full md:w-2/3 lg:w-3/4 flex flex-col bg-[#efeae2] relative h-full">
 <!-- Subtle WhatsApp-like chat background pattern (optional) -->
 <div class="absolute inset-0 opacity-[0.04] pointer-events-none" style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-repeat: repeat;"></div>

 @if($activeConversationId)
 @php
 $activeConv = $conversations->firstWhere('id', $activeConversationId);
 @endphp
 
 <!-- Chat Header -->
 <div class="px-6 py-3 bg-white border-b border-gray-200 flex justify-between items-center shadow-sm z-10 sticky top-0">
 <div class="flex items-center space-x-4">
 <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-lg shadow-sm">
 {{ substr($activeConv->customer_name ?: $activeConv->phone_number, 0, 1) }}
 </div>
 <div>
 <div class="font-bold text-gray-900 leading-tight">{{ $activeConv->customer_name ?: $activeConv->phone_number }}</div>
 <div class="text-xs text-gray-500 mt-0.5 flex items-center">
 <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
 {{ $activeConv->phone_number }}
 </div>
 </div>
 </div>
 <button wire:click="toggleBotMode" class="text-sm font-medium px-4 py-2 border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 flex items-center bg-white z-20 relative cursor-pointer">
 @if($activeConv->status === 'bot')
 <span class="w-2 h-2 rounded-full bg-amber-500 mr-2"></span> Switch to Human
 @else
 <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span> Switch to Bot
 @endif
 </button>
 </div>

 <!-- Messages -->
 <div class="flex-1 p-6 overflow-y-auto flex flex-col space-y-4 z-10 scrollbar-thin scrollbar-thumb-gray-300">
 @forelse($messages as $msg)
 <div class="flex {{ $msg->direction === 'outbound' ? 'justify-end' : 'justify-start' }} animate-fade-in-up">
 <div class="max-w-[75%] lg:max-w-[60%] relative group">
 <div class="rounded-2xl px-4 py-2 shadow-sm relative {{ $msg->direction === 'outbound' ? 'bg-indigo-600 text-white rounded-br-sm' : 'bg-white text-gray-800 rounded-bl-sm border border-gray-100' }}">
 
 @if($msg->type === 'text')
 <div class="whitespace-pre-wrap break-words text-[15px] leading-relaxed">{{ $msg->content }}</div>
 @else
 <div class="flex items-center space-x-2 italic text-sm opacity-80">
 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
 <span>[{{ ucfirst($msg->type) }} message]</span>
 </div>
 @endif
 
 <div class="text-[10px] mt-1.5 flex items-center justify-end {{ $msg->direction === 'outbound' ? 'text-indigo-200' : 'text-gray-400' }}">
 {{ $msg->created_at->format('g:i A') }}
 @if($msg->direction === 'outbound')
 <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
 @endif
 </div>
 </div>
 </div>
 </div>
 @empty
 <div class="flex flex-col items-center justify-center h-full opacity-50">
 <div class="bg-black/10 text-gray-600 text-xs font-semibold px-4 py-1.5 rounded-full mb-4">
 Today
 </div>
 <p class="text-gray-500 text-sm">Send a message to start the conversation.</p>
 </div>
 @endforelse
 </div>

 <!-- Input Area -->
 <div class="p-4 bg-[#f0f2f5] border-t border-gray-200 z-10 sticky bottom-0">
 <form wire:submit.prevent="sendMessage" class="flex items-center space-x-3 max-w-4xl mx-auto">
 <div class="flex-1 relative">
 <input type="text" wire:model="newMessage" placeholder="Type a message..." class="w-full rounded-full border-0 shadow-sm focus:ring-2 focus:ring-indigo-500 py-3 px-5 text-gray-700 placeholder-gray-400 outline-none" required autocomplete="off">
 </div>
 <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-full w-12 h-12 flex flex-shrink-0 items-center justify-center transition shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 ml-0.5">
 <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
 </svg>
 </button>
 </form>
 </div>
 @else
 <!-- Empty State -->
 <div class="flex-1 flex items-center justify-center text-gray-400 flex-col z-10 relative">
 <div class="bg-white p-6 rounded-full shadow-sm mb-6">
 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-16 h-16 text-indigo-200">
 <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
 </svg>
 </div>
 <h3 class="text-xl font-semibold text-gray-600 mb-2">Your Messages</h3>
 <p class="text-sm text-gray-400">Select a conversation from the left to start messaging.</p>
 </div>
 @endif
 </div>

 <style>
 .animate-fade-in-up {
 animation: fadeInUp 0.3s ease-out;
 }
 @keyframes fadeInUp {
 from { opacity: 0; transform: translateY(10px); }
 to { opacity: 1; transform: translateY(0); }
 }
 /* Custom Scrollbar */
 .scrollbar-thin::-webkit-scrollbar {
 width: 6px;
 }
 .scrollbar-thin::-webkit-scrollbar-track {
 background: transparent;
 }
 .scrollbar-thin::-webkit-scrollbar-thumb {
 background-color: #d1d5db;
 border-radius: 20px;
 }
 </style>
</div>
