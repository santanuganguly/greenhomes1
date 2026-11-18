<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class Inbox extends Component
{
    public $conversations = [];
    public $activeConversationId = null;
    public $messages = [];
    public $newMessage = '';

    public function mount()
    {
        $this->loadConversations();
    }

    public function loadConversations()
    {
        $this->conversations = Conversation::orderBy('last_message_at', 'desc')->take(50)->get();
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
            $this->messages = Message::where('conversation_id', $this->activeConversationId)
                                     ->orderBy('created_at', 'asc')
                                     ->get();
        }
    }

    public function refreshData()
    {
        $this->loadConversations();
        $this->loadMessages();
    }

    public function sendMessage()
    {
        $this->validate([
            'newMessage' => 'required|string|max:1000'
        ]);

        $conversation = Conversation::find($this->activeConversationId);
        if (!$conversation) return;

        // Save locally first
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'type' => 'text',
            'content' => $this->newMessage,
            'status' => 'sent'
        ]);

        // Send to Meta Cloud API
        $this->sendToMeta($conversation->phone_number, $this->newMessage);

        $conversation->update([
            'last_message' => 'You: ' . $this->newMessage,
            'last_message_at' => now(),
            // When a human agent replies, automatically switch to human mode
            'status' => 'human' 
        ]);

        $this->newMessage = '';
        $this->loadMessages();
        $this->loadConversations();
    }

    private function sendToMeta($phoneNumber, $text)
    {
        $token = Setting::where('key', 'meta_access_token')->first()?->value ?? env('META_ACCESS_TOKEN');
        $phoneId = Setting::where('key', 'meta_phone_number_id')->first()?->value ?? env('META_PHONE_NUMBER_ID');

        if (!$token || !$phoneId) return;

        Http::withoutVerifying()->withToken($token)->post("https://graph.facebook.com/v19.0/{$phoneId}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phoneNumber,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text
            ]
        ]);
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
