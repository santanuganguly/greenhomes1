<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    protected $fillable = [
        'phone_number',
        'customer_name',
        'unread_count',
        'last_message',
        'last_message_at',
        'status',
        'assigned_to',
        'current_node_id',
        'context'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'context' => 'array'
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
