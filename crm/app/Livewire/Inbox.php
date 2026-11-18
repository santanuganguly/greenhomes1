<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Http;

class Inbox extends Component
{
    public $conversations = [];
    public $activeConversationId = null;
    public $chatMessages = [];
    public $newMessage = '';

    public function mount()
    {
        $this->loadConversations();
    }

    public function loadConversations()
    {
        $this->conversations = Conversation::orderBy('last_message_at', 'desc')->get();
    }

    public function selectConversation($id)
    {
        $this->activeConversationId = $id;
        $this->loadMessages();
        
        // Mark as read
        $conversation = Conversation::find($id);
        if ($conversation && $conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
            $this->loadConversations();
        }
    }

    public function loadMessages()
    {
        if ($this->activeConversationId) {
            $this->chatMessages = Message::where('conversation_id', $this->activeConversationId)
                                     ->orderBy('created_at', 'asc')
                                     ->get();
        }
    }

    public function sendMessage()
    {
        $this->validate([
            'newMessage' => 'required|string|max:1000'
        ]);

        $conversation = Conversation::find($this->activeConversationId);
        if (!$conversation) return;

        // WhatsAppService will automatically log the outbound message and update last_message
        app(\App\Services\WhatsAppService::class)->sendText($conversation->phone_number, $this->newMessage);

        $conversation->update([
            'status' => 'human' 
        ]);

        $this->newMessage = '';
        $this->loadMessages();
        $this->loadConversations();
    }

    public function toggleBotMode()
    {
        $conversation = Conversation::find($this->activeConversationId);
        if ($conversation) {
            $conversation->status = $conversation->status === 'bot' ? 'human' : 'bot';
            $conversation->save();
            $this->loadConversations();
        }
    }

    public function render()
    {
        return view('livewire.inbox')->layout('layouts.app');
    }
}
