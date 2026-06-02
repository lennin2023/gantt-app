<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;

trait HasAuditFields
{
    public static function bootHasAuditFields(): void
    {
        $class = get_called_class();

        $class::creating(function ($model) {
            $model->created_by ??= Auth::id();
            $model->updated_by ??= Auth::id();
        });

        $class::updating(function ($model) {
            $model->updated_by ??= Auth::id();
        });
    }
}
