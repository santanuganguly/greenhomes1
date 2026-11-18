<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasOrganization;

class Project extends Model
{
    use HasFactory, HasOrganization;

    protected $fillable = [
        'name', 'slug', 'business_vertical', 'description', 'location', 'is_active'
    ];

    public function configs(): HasMany
    {
        return $this->hasMany(ProjectConfig::class)->orderBy('sort_order');
    }
}
