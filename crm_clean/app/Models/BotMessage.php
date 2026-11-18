<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasOrganization;

class BotMessage extends Model
{
    use HasFactory, HasOrganization;

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
