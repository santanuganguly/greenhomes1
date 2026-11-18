<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\BotMessage;

class BotMessageEditor extends Component
{
    public $messages;
    public $editingId = null;
    public $search = '';
    public $filterGroup = '';

    // Edit form fields
    public $editDisplayName   = '';
    public $editHeaderText    = '';
    public $editBodyText      = '';
    public $editFooterText    = '';
    public $editMetaTemplate  = '';
    public $editMetaStatus    = 'not_connected';
    public $editMetaLanguage  = 'en';
    public $editIsActive      = true;
    public $editButtons       = [];
    public $successMessage    = '';

    public function mount()
    {
        $this->loadMessages();
    }

    public function loadMessages()
    {
        $query = BotMessage::query();
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('internal_name', 'like', "%{$this->search}%")
                  ->orWhere('display_name', 'like', "%{$this->search}%");
            });
        }
        if ($this->filterGroup) {
            $query->where('flow_group', $this->filterGroup);
        }
        $this->messages = $query->orderBy('flow_group')->orderBy('internal_name')->get();
    }

    public function updatedSearch()   { $this->loadMessages(); }
    public function updatedFilterGroup() { $this->loadMessages(); }

    public function edit(int $id)
    {
        $msg = BotMessage::findOrFail($id);
        $this->editingId        = $id;
        $this->editDisplayName  = $msg->display_name;
        $this->editHeaderText   = $msg->header_text ?? '';
        $this->editBodyText     = $msg->body_text ?? '';
        $this->editFooterText   = $msg->footer_text ?? '';
        $this->editMetaTemplate = $msg->meta_template_name ?? '';
        $this->editMetaStatus   = $msg->meta_template_status;
        $this->editMetaLanguage = $msg->meta_template_language ?? 'en';
        $this->editIsActive     = $msg->is_active;
        $this->editButtons      = $msg->buttons ?? [];
    }

    public function saveMessage()
    {
        $this->validate([
            'editDisplayName' => 'required|string',
            'editBodyText'    => 'required|string',
        ]);

        BotMessage::where('id', $this->editingId)->update([
            'display_name'          => $this->editDisplayName,
            'header_text'           => $this->editHeaderText ?: null,
            'body_text'             => $this->editBodyText,
            'footer_text'           => $this->editFooterText ?: null,
            'meta_template_name'    => $this->editMetaTemplate ?: null,
            'meta_template_status'  => $this->editMetaStatus,
            'meta_template_language'=> $this->editMetaLanguage,
            'is_active'             => $this->editIsActive,
        ]);

        $this->successMessage = '✅ Message saved successfully!';
        $this->editingId = null;
        $this->loadMessages();

        $this->dispatch('messageSaved');
    }

    public function cancelEdit()
    {
        $this->editingId = null;
        $this->successMessage = '';
    }

    public function getFlowGroupsProperty()
    {
        return BotMessage::select('flow_group')->distinct()->orderBy('flow_group')->pluck('flow_group')->filter();
    }

    public function render()
    {
        return view('livewire.bot-message-editor')->layout('layouts.app');
    }
}
