<?php

namespace App\Livewire\Marketing;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Campaign;
use App\Jobs\SendCampaignMessageJob;

#[Layout('layouts.app')]
class CampaignsList extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function sendCampaign($id)
    {
        $campaign = Campaign::findOrFail($id);
        if ($campaign->status === 'draft') {
            $campaign->status = 'running';
            $campaign->save();
            
            // Dispatch jobs
            foreach ($campaign->recipients as $recipient) {
                if ($recipient->status === 'pending') {
                    $recipient->status = 'queued';
                    $recipient->save();
                    SendCampaignMessageJob::dispatch($recipient);
                }
            }
            
            session()->flash('message', 'Campaign started. Messages are being sent in the background.');
        }
    }

    public function pauseCampaign($id)
    {
        $campaign = Campaign::findOrFail($id);
        if ($campaign->status === 'running') {
            $campaign->status = 'paused';
            $campaign->save();
            session()->flash('message', 'Campaign paused. No further messages will be dispatched until resumed.');
        }
    }

    public function resumeCampaign($id)
    {
        $campaign = Campaign::findOrFail($id);
        if ($campaign->status === 'paused') {
            $campaign->status = 'running';
            $campaign->save();
            
            // Re-dispatch any paused/queued recipients
            $recipients = $campaign->recipients()->whereIn('status', ['pending', 'queued'])->get();
            foreach ($recipients as $recipient) {
                $recipient->status = 'queued';
                $recipient->save();
                SendCampaignMessageJob::dispatch($recipient);
            }
            
            session()->flash('message', 'Campaign resumed.');
        }
    }

    public function cancelCampaign($id)
    {
        $campaign = Campaign::findOrFail($id);
        if (in_array($campaign->status, ['running', 'paused', 'draft'])) {
            $campaign->status = 'cancelled';
            $campaign->save();
            
            // Mark remaining as cancelled
            $campaign->recipients()->whereIn('status', ['pending', 'queued'])->update(['status' => 'cancelled']);
            
            session()->flash('message', 'Campaign cancelled. Remaining messages will not be sent.');
        }
    }

    public function render()
    {
        $query = Campaign::query()->orderBy('created_at', 'desc');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('template_name', 'like', '%' . $this->search . '%');
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $campaigns = $query->paginate(10);

        return view('livewire.marketing.campaigns-list', compact('campaigns'));
    }
}
