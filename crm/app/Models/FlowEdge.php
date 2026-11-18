<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowEdge extends Model
{
    protected $fillable = [
        'source_node_id',
        'target_node_id',
        'condition_payload',
        'save_value'
    ];

    public function sourceNode(): BelongsTo
    {
        return $this->belongsTo(FlowNode::class, 'source_node_id');
    }

    public function targetNode(): BelongsTo
    {
        return $this->belongsTo(FlowNode::class, 'target_node_id');
    }
}
