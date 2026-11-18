<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasOrganization;

class BulkMessage extends Model
{
    use HasFactory, HasOrganization;

    protected $fillable = [
        'bulk_campaign_id',
        'phone_number',
        'contact_name',
        'status',
        'wamid',
        'error_message',
    ];

    public function campaign()
    {
        return $this->belongsTo(BulkCampaign::class, 'bulk_campaign_id');
    }
}
