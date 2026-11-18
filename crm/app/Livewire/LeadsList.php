<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Lead;

class LeadsList extends Component
{
    public $leads = [];

    public function mount()
    {
        $this->loadLeads();
    }

    public function loadLeads()
    {
        $this->leads = Lead::orderBy('created_at', 'desc')->get();
    }

    public function render()
    {
        return view('livewire.leads-list')->layout('layouts.app');
    }
}
