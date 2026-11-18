<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FlowNode extends Model
{
    use HasFactory, HasOrganization;

    protected $fillable = [
        'flow_id',
        'bot_message_id',
        'name',
        'type',
        'template_name',
        'text_content',
        'expected_lead_field',
        'triggers_human_handover',
        'is_start',
        'sort_order',
        'input_type',
    ];

    protected $casts = [
        'triggers_human_handover' => 'boolean',
        'is_start'                => 'boolean',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    public function botMessage(): BelongsTo
    {
        return $this->belongsTo(BotMessage::class);
    }

    public function edges(): HasMany
    {
        return $this->hasMany(FlowEdge::class, 'source_node_id');
    }
}
