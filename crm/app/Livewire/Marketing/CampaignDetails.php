<?php

namespace App\Livewire\Marketing;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Campaign;
use App\Models\CampaignRecipient;

#[Layout('layouts.app')]
class CampaignDetails extends Component
{
    use WithPagination;

    public Campaign $campaign;
    public $search = '';
    public $statusFilter = '';

    public function mount($id)
    {
        $this->campaign = Campaign::withCount([
            'recipients as pending_count' => function ($query) { $query->where('status', 'pending'); },
            'recipients as queued_count' => function ($query) { $query->where('status', 'queued'); },
            'recipients as sent_count' => function ($query) { $query->where('status', 'sent'); },
            'recipients as delivered_count' => function ($query) { $query->where('status', 'delivered'); },
            'recipients as read_count' => function ($query) { $query->where('status', 'read'); },
            'recipients as failed_count' => function ($query) { $query->where('status', 'failed'); },
        ])->findOrFail($id);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->campaign->recipients();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('phone', 'like', '%' . $this->search . '%')
                  ->orWhere('name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $recipients = $query->paginate(20);

        return view('livewire.marketing.campaign-details', compact('recipients'));
    }
}
