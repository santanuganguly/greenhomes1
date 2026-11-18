<?php

namespace App\Traits;

use App\Models\Scopes\OrganizationScope;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

trait HasOrganization
{
    protected static function bootHasOrganization()
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function ($model) {
            // Automatically assign the organization_id upon creation
            if (!$model->organization_id) {
                if (Config::has('tenant.organization_id')) {
                    $model->organization_id = Config::get('tenant.organization_id');
                } elseif (Auth::hasUser() && Auth::user()->organization_id) {
                    $model->organization_id = Auth::user()->organization_id;
                } else {
                    // Fallback to 1 for safety during migration phase, or throw exception ideally
                    $model->organization_id = 1;
                }
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
