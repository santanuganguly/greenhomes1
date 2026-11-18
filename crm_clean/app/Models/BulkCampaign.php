<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasOrganization;

class BulkCampaign extends Model
{
    use HasFactory, HasOrganization;

    protected $fillable = [
        'name',
        'message_template',
        'total_contacts',
    ];

    public function messages()
    {
        return $this->hasMany(BulkMessage::class);
    }
}
