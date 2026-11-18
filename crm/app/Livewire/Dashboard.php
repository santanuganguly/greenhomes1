<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Lead;
use App\Models\Conversation;
use App\Models\Message;

class Dashboard extends Component
{
    public function render()
    {
        $stats = [
            'total_leads' => Lead::count(),
            'total_conversations' => Conversation::count(),
            'unread_conversations' => Conversation::where('unread_count', '>', 0)->count(),
            'total_messages' => Message::count(),
        ];

        $recentLeads = Lead::orderBy('created_at', 'desc')->take(6)->get();
        $recentConversations = Conversation::orderBy('last_message_at', 'desc')->take(5)->get();

        return view('livewire.dashboard', [
            'stats' => $stats,
            'recentLeads' => $recentLeads,
            'recentConversations' => $recentConversations,
        ])->layout('layouts.app');
    }
}
