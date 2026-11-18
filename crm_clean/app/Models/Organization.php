<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $guarded = [];

    public function settings()
    {
        return $this->hasOne(OrganizationSetting::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
