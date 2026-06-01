<?php

namespace App\Models\Concerns;

use App\Observers\AuditFieldsObserver;

trait HasAuditFields
{
    abstract public static function observe(mixed $classes): void;

    public static function bootHasAuditFields(): void
    {
        static::observe(AuditFieldsObserver::class);
    }
}
