<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasOrganization;

class Lead extends Model
{
    use HasOrganization;

    protected $fillable = [
        'organization_id',
        'conversation_id',
        'customer_name',
        'phone_number',
        'business_vertical',
        'project',
        'configuration',
        'owns_land',
        'land_location',
        'plot_size',
        'home_type',
        'floors',
        'area',
        'builtup_area',
        'budget',
        'timeline',
        'requirement',
        'human_requested',
        'site_visit_date',
        'site_visit_time',
        'score',
        'status',
        'pipeline_stage',
        'lead_source',
        'temperature',
        'assigned_to',
        'last_contact_date',
        'next_follow_up_date',
        'sales_value',
        'lost_reason',
    ];

    protected $casts = [
        'human_requested' => 'boolean',
        'last_contact_date' => 'datetime',
        'next_follow_up_date' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function stageHistories()
    {
        return $this->hasMany(LeadStageHistory::class)->orderBy('created_at', 'desc');
    }
}
