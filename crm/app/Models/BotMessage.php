<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotMessage extends Model
{
    protected $fillable = [
        'internal_name',
        'display_name',
        'flow_group',
        'business_vertical',
        'header_text',
        'body_text',
        'footer_text',
        'buttons',
        'list_sections',
        'list_button_label',
        'message_type',
        'meta_template_name',
        'meta_template_language',
        'meta_template_status',
        'is_active',
    ];

    protected $casts = [
        'buttons'       => 'array',
        'list_sections' => 'array',
        'is_active'     => 'boolean',
    ];

    /**
     * Returns a badge color class based on meta template status.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->meta_template_status) {
            'approved'      => 'green',
            'pending'       => 'yellow',
            'rejected'      => 'red',
            'paused'        => 'orange',
            default         => 'gray', // not_connected
        };
    }
}
