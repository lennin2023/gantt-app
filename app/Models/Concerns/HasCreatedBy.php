<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;

trait HasCreatedBy
{
    public static function bootHasCreatedBy(): void
    {
        $class = get_called_class();

        $class::creating(function ($model) {
            $model->created_by ??= Auth::id();

            if (! $model->usesTimestamps()) {
                $model->created_at ??= now();
            }
        });
    }
}
