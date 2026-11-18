<?php

namespace App\Livewire\Marketing;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Campaign;

#[Layout('layouts.app')]
class MarketingDashboard extends Component
{
    public function render()
    {
        $totalCampaigns = Campaign::count();
        $totalRecipients = Campaign::sum('total_recipients');
        $totalDelivered = Campaign::sum('delivered_count');
        $totalSent = Campaign::sum('sent_count');
        $totalRead = Campaign::sum('read_count');

        $deliveryRate = $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100) : 0;
        $readRate = $totalDelivered > 0 ? round(($totalRead / $totalDelivered) * 100) : 0;

        $recentCampaigns = Campaign::orderBy('created_at', 'desc')->take(5)->get();

        return view('livewire.marketing.marketing-dashboard', compact(
            'totalCampaigns',
            'totalRecipients',
            'deliveryRate',
            'readRate',
            'recentCampaigns'
        ));
    }
}
