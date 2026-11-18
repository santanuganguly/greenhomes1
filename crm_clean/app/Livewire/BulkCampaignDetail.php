<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\BulkCampaign;
use App\Models\BulkMessage;

class BulkCampaignDetail extends Component
{
    public $campaignId;
    public $campaign;

    public function mount($id)
    {
        $this->campaignId = $id;
        $this->campaign = BulkCampaign::findOrFail($id);
    }

    public function render()
    {
        $messages = BulkMessage::where('bulk_campaign_id', $this->campaignId)->get();
        return view('livewire.bulk-campaign-detail', [
            'messages' => $messages
        ])->layout('layouts.app');
    }
}
