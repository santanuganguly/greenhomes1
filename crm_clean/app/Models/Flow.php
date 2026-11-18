<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasOrganization;

class Flow extends Model
{
    use HasOrganization;

    protected $fillable = [
        'name',
        'group_name',
        'business_vertical',
        'is_active',
        'is_default_welcome',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'is_default_welcome' => 'boolean',
    ];

    public function nodes(): HasMany
    {
        return $this->hasMany(FlowNode::class)->orderBy('sort_order')->orderBy('id');
    }

    public function startNode(): ?FlowNode
    {
        return $this->nodes()->where('is_start', true)->first()
            ?? $this->nodes()->first();
    }
}
