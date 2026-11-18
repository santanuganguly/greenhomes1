<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Lead;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ManagementDashboard extends Component
{
    public $dateFilter = 'this_month';
    public $customStartDate = null;
    public $customEndDate = null;

    public function updatedDateFilter()
    {
        // Reactively update when filter changes
    }

    protected function getDateRange()
    {
        $start = null;
        $end = null;

        switch ($this->dateFilter) {
            case 'today':
                $start = Carbon::today();
                $end = Carbon::today()->endOfDay();
                break;
            case '7_days':
                $start = Carbon::now()->subDays(7)->startOfDay();
                $end = Carbon::now()->endOfDay();
                break;
            case '30_days':
                $start = Carbon::now()->subDays(30)->startOfDay();
                $end = Carbon::now()->endOfDay();
                break;
            case 'this_month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;
            case 'last_month':
                $start = Carbon::now()->subMonth()->startOfMonth();
                $end = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'custom':
                if ($this->customStartDate && $this->customEndDate) {
                    $start = Carbon::parse($this->customStartDate)->startOfDay();
                    $end = Carbon::parse($this->customEndDate)->endOfDay();
                } else {
                    $start = Carbon::now()->startOfMonth();
                    $end = Carbon::now()->endOfMonth();
                }
                break;
        }

        return [$start, $end];
    }

    public function getKpisProperty()
    {
        [$start, $end] = $this->getDateRange();

        $aggregates = Lead::whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('COUNT(*) as period_leads'),
                DB::raw('SUM(CASE WHEN temperature = "Hot" THEN 1 ELSE 0 END) as hot_leads'),
                DB::raw('SUM(CASE WHEN pipeline_stage = "Qualified" THEN 1 ELSE 0 END) as qualified'),
                DB::raw('SUM(CASE WHEN pipeline_stage IN ("Site Visit Planned", "Site Visit Completed") THEN 1 ELSE 0 END) as site_visits'),
                DB::raw('SUM(CASE WHEN pipeline_stage = "Negotiation" THEN 1 ELSE 0 END) as negotiations'),
                DB::raw('SUM(CASE WHEN pipeline_stage = "Booking" THEN 1 ELSE 0 END) as bookings'),
                DB::raw('SUM(CASE WHEN pipeline_stage = "Converted / Customer" THEN 1 ELSE 0 END) as converted'),
                DB::raw('SUM(CASE WHEN pipeline_stage = "Converted / Customer" THEN sales_value ELSE 0 END) as sales_value')
            )
            ->first();

        return [
            'total_lifetime_leads' => Lead::count(),
            'period_leads' => (int) ($aggregates->period_leads ?? 0),
            'hot_leads' => (int) ($aggregates->hot_leads ?? 0),
            'qualified' => (int) ($aggregates->qualified ?? 0),
            'site_visits' => (int) ($aggregates->site_visits ?? 0),
            'negotiations' => (int) ($aggregates->negotiations ?? 0),
            'bookings' => (int) ($aggregates->bookings ?? 0),
            'converted' => (int) ($aggregates->converted ?? 0),
            'sales_value' => (float) ($aggregates->sales_value ?? 0),
        ];
    }

    public function getFunnelProperty()
    {
        [$start, $end] = $this->getDateRange();
        
        $aggregates = Lead::whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('COUNT(*) as total_leads'),
                DB::raw('SUM(CASE WHEN pipeline_stage IN ("Contacted", "Qualified", "Site Visit Planned", "Site Visit Completed", "Negotiation", "Booking", "Converted / Customer") THEN 1 ELSE 0 END) as contacted'),
                DB::raw('SUM(CASE WHEN pipeline_stage IN ("Qualified", "Site Visit Planned", "Site Visit Completed", "Negotiation", "Booking", "Converted / Customer") THEN 1 ELSE 0 END) as qualified'),
                DB::raw('SUM(CASE WHEN pipeline_stage IN ("Site Visit Planned", "Site Visit Completed", "Negotiation", "Booking", "Converted / Customer") THEN 1 ELSE 0 END) as site_visit'),
                DB::raw('SUM(CASE WHEN pipeline_stage IN ("Negotiation", "Booking", "Converted / Customer") THEN 1 ELSE 0 END) as negotiation'),
                DB::raw('SUM(CASE WHEN pipeline_stage IN ("Booking", "Converted / Customer") THEN 1 ELSE 0 END) as booking'),
                DB::raw('SUM(CASE WHEN pipeline_stage = "Converted / Customer" THEN 1 ELSE 0 END) as converted')
            )
            ->first();

        return [
            'Leads' => (int) ($aggregates->total_leads ?? 0),
            'Contacted' => (int) ($aggregates->contacted ?? 0),
            'Qualified' => (int) ($aggregates->qualified ?? 0),
            'Site Visit' => (int) ($aggregates->site_visit ?? 0),
            'Negotiation' => (int) ($aggregates->negotiation ?? 0),
            'Booking' => (int) ($aggregates->booking ?? 0),
            'Converted' => (int) ($aggregates->converted ?? 0),
        ];
    }

    public function getLeadSourcesProperty()
    {
        [$start, $end] = $this->getDateRange();
        
        $sources = Lead::whereBetween('created_at', [$start, $end])
            ->select('lead_source', DB::raw('count(*) as total'))
            ->groupBy('lead_source')
            ->orderByDesc('total')
            ->get();
            
        return $sources;
    }

    public function getSalesPerformanceProperty()
    {
        [$start, $end] = $this->getDateRange();
        
        $performance = Lead::with('assignedUser')
            ->whereBetween('created_at', [$start, $end])
            ->select(
                'assigned_to',
                DB::raw('count(*) as total_leads'),
                DB::raw('SUM(CASE WHEN pipeline_stage = "Qualified" THEN 1 ELSE 0 END) as qualified'),
                DB::raw('SUM(CASE WHEN pipeline_stage IN ("Site Visit Planned", "Site Visit Completed") THEN 1 ELSE 0 END) as site_visits'),
                DB::raw('SUM(CASE WHEN pipeline_stage = "Booking" THEN 1 ELSE 0 END) as bookings'),
                DB::raw('SUM(CASE WHEN pipeline_stage = "Converted / Customer" THEN 1 ELSE 0 END) as converted'),
                DB::raw('SUM(CASE WHEN pipeline_stage = "Converted / Customer" THEN sales_value ELSE 0 END) as sales_value')
            )
            ->groupBy('assigned_to')
            ->get();
            
        return $performance;
    }

    public function getFollowUpHealthProperty()
    {
        $today = Carbon::today();
        
        return [
            'today' => Lead::whereDate('next_follow_up_date', $today)->count(),
            'overdue' => Lead::where('next_follow_up_date', '<', $today)->whereNotIn('pipeline_stage', ['Converted / Customer', 'Lost'])->count(),
            'uncontacted' => Lead::whereNull('last_contact_date')->where('pipeline_stage', '!=', 'Lost')->count(),
            'hot_no_followup' => Lead::where('temperature', 'Hot')->whereNull('next_follow_up_date')->whereNotIn('pipeline_stage', ['Converted / Customer', 'Lost'])->count(),
        ];
    }

    public function getLostReasonsProperty()
    {
        [$start, $end] = $this->getDateRange();
        
        return Lead::whereBetween('created_at', [$start, $end])
            ->where('pipeline_stage', 'Lost')
            ->select('lost_reason', DB::raw('count(*) as total'))
            ->groupBy('lost_reason')
            ->orderByDesc('total')
            ->get();
    }
    
    public function getSystemTelemetryProperty()
    {
        return [
            'total_conversations' => Conversation::count(),
            'total_messages' => Message::count(),
            'active_projects' => Project::where('is_active', true)->count(),
        ];
    }

    public function render()
    {
        return view('livewire.management-dashboard', [
            'kpis' => $this->kpis,
            'funnel' => $this->funnel,
            'leadSources' => $this->leadSources,
            'salesPerformance' => $this->salesPerformance,
            'followUpHealth' => $this->followUpHealth,
            'lostReasons' => $this->lostReasons,
            'telemetry' => $this->systemTelemetry,
        ])->layout('layouts.app');
    }
}
