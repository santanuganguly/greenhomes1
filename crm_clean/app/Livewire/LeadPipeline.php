<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Lead;
use App\Models\LeadStageHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeadPipeline extends Component
{
    public $search = '';
    public $filterProject = '';
    public $filterSource = '';
    public $filterAssignedTo = '';
    public $filterTemperature = '';
    
    public $stages = [
        'New Lead',
        'Contacted',
        'Qualified',
        'Site Visit Planned',
        'Site Visit Completed',
        'Negotiation',
        'Booking',
        'Converted / Customer',
        'Lost',
    ];

    public function updateLeadStage($leadId, $newStage)
    {
        if (!in_array($newStage, $this->stages)) {
            return;
        }

        $lead = Lead::find($leadId);
        if ($lead && $lead->pipeline_stage !== $newStage) {
            $oldStage = $lead->pipeline_stage;
            $lead->pipeline_stage = $newStage;
            $lead->save();

            LeadStageHistory::create([
                'lead_id' => $lead->id,
                'previous_stage' => $oldStage,
                'new_stage' => $newStage,
                'user_id' => Auth::id(),
            ]);
        }
    }

    public function getLeadsProperty()
    {
        $query = Lead::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('customer_name', 'like', '%' . $this->search . '%')
                  ->orWhere('phone_number', 'like', '%' . $this->search . '%')
                  ->orWhere('project', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterProject) {
            $query->where('project', $this->filterProject);
        }

        if ($this->filterSource) {
            $query->where('lead_source', $this->filterSource);
        }

        if ($this->filterTemperature) {
            $query->where('temperature', $this->filterTemperature);
        }

        if ($this->filterAssignedTo) {
            $query->where('assigned_to', $this->filterAssignedTo);
        }

        $leads = $query->orderBy('created_at', 'desc')->take(200)->get();
        
        $grouped = [];
        foreach ($this->stages as $stage) {
            $grouped[$stage] = [];
        }

        foreach ($leads as $lead) {
            $stage = $lead->pipeline_stage ?? 'New Lead';
            if (isset($grouped[$stage])) {
                $grouped[$stage][] = $lead;
            } else {
                $grouped['New Lead'][] = $lead; // Fallback
            }
        }

        return $grouped;
    }

    public function getFunnelSummaryProperty()
    {
        $totalsQuery = Lead::select('pipeline_stage', DB::raw('count(*) as count'))
            ->groupBy('pipeline_stage')
            ->pluck('count', 'pipeline_stage')
            ->toArray();

        $totals = [];
        foreach ($this->stages as $stage) {
            $totals[$stage] = $totalsQuery[$stage] ?? 0;
        }
        
        $totalLeads = array_sum($totals);
        $converted = $totals['Converted / Customer'] ?? 0;
        $lost = $totals['Lost'] ?? 0;
        
        return [
            'totals' => $totals,
            'total_leads' => $totalLeads,
            'converted' => $converted,
            'lost' => $lost,
            'conversion_rate' => $totalLeads > 0 ? round(($converted / $totalLeads) * 100, 1) : 0,
        ];
    }

    public function render()
    {
        return view('livewire.lead-pipeline', [
            'leadsByStage' => $this->leads,
            'funnel' => $this->funnelSummary,
        ])->layout('layouts.app');
    }
}
