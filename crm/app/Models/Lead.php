<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = [
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
        'marketing_opt_out',
        'opted_out_at',
        'opt_out_reason',
        'source',
    ];

    protected $casts = [
        'human_requested' => 'boolean',
        'marketing_opt_out' => 'boolean',
        'opted_out_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
