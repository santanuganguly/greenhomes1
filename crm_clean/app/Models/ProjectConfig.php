<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasOrganization;

class ProjectConfig extends Model
{
    use HasFactory, HasOrganization;

    protected $fillable = [
        'project_id', 'config_name', 'price_label', 'price_type', 'sort_order', 'is_active'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
