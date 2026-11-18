<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    protected $fillable = [
        'name',
        'description',
        'business_vertical',
        'project',
        'template_name',
        'campaign_type',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'total_recipients',
        'queued_count',
        'sent_count',
        'delivered_count',
        'read_count',
        'failed_count',
        'skipped_count',
        'opted_out_count',
        'created_by'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CampaignLog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCompleted(): bool
    {
        $terminalStatuses = ['completed', 'completed_with_errors', 'failed', 'cancelled'];
        return in_array($this->status, $terminalStatuses);
    }
}
