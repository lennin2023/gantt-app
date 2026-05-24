<?php

namespace App\Providers;

use App\Events\MilestoneCreated;
use App\Events\MilestoneDeleted;
use App\Events\MilestoneRestored;
use App\Events\MilestoneUpdated;
use App\Events\ProjectCreated;
use App\Events\ProjectDeleted;
use App\Events\ProjectUpdated;
use App\Events\ProjectUserAssigned;
use App\Events\ProjectUserRemoved;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TaskUpdated;
use App\Listeners\LogMilestoneActivity;
use App\Listeners\LogProjectActivity;
use App\Listeners\LogProjectUserActivity;
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

        ProjectCreated::class => [
            LogProjectActivity::class,
        ],
        ProjectUpdated::class => [
            LogProjectActivity::class,
        ],
        ProjectDeleted::class => [
            LogProjectActivity::class,
        ],

        MilestoneCreated::class => [
            LogMilestoneActivity::class,
        ],
        MilestoneUpdated::class => [
            LogMilestoneActivity::class,
        ],
        MilestoneDeleted::class => [
            LogMilestoneActivity::class,
        ],
        MilestoneRestored::class => [
            LogMilestoneActivity::class,
        ],

        ProjectUserAssigned::class => [
            LogProjectUserActivity::class,
        ],
        ProjectUserRemoved::class => [
            LogProjectUserActivity::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
