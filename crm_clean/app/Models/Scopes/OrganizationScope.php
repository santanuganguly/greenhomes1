<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // If we're setting a system-level override (e.g. during webhook processing)
        if (Config::has('tenant.organization_id')) {
            $builder->where($model->getTable() . '.organization_id', Config::get('tenant.organization_id'));
            return;
        }

        // Check if a user is authenticated
        if (Auth::hasUser()) {
            $user = Auth::user();
            
            // If the user is a super admin, they might be able to see everything (optional logic)
            // if ($user->is_super_admin) return;
            
            if ($user->organization_id) {
                $builder->where($model->getTable() . '.organization_id', $user->organization_id);
            }
        }
    }
}
