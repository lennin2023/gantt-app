<?php

namespace App\Providers;

use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TaskUpdated;
use App\Listeners\LogTaskActivity;
use App\Listeners\RefreshProjectStatus;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TaskCreated::class => [
            LogTaskActivity::class,
        ],
        TaskUpdated::class => [
            LogTaskActivity::class,
        ],
        TaskDeleted::class => [
            LogTaskActivity::class,
        ],
        TaskCompleted::class => [
            LogTaskActivity::class,
            RefreshProjectStatus::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
