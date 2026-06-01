<?php

namespace App\Models\Concerns;

use App\Observers\CreatedByObserver;

trait HasCreatedBy
{
    abstract public static function observe(mixed $classes): void;

    public static function bootHasCreatedBy(): void
    {
        static::observe(CreatedByObserver::class);
    }
}
