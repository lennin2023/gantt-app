<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreatedByObserver
{
    public function creating(Model $model): void
    {
        $model->created_by ??= Auth::id();

        if (! $model->usesTimestamps()) {
            $model->created_at ??= now();
        }
    }
}
