<?php

namespace App\Providers;

use App\Events\MilestoneCreated;
use App\Events\MilestoneDeleted;
use App\Events\MilestoneRestored;
use App\Events\MilestoneUpdated;
use App\Events\ProjectCreated;
use App\Events\ProjectUpdated;
use App\Events\ProjectUserAssigned;
use App\Events\ProjectUserRemoved;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Listeners\LogMilestoneActivity;
use App\Listeners\LogProjectActivity;
use App\Listeners\LogProjectUserActivity;
use App\Listeners\LogTaskActivity;
use App\Listeners\RefreshProjectStatus;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Policies\MilestonePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\TaskAssignmentPolicy;
use App\Policies\TaskPolicy;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\MilestoneRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\ProjectUserRepositoryInterface;
use App\Repositories\Contracts\TaskAssignmentRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Eloquent\DashboardRepository;
use App\Repositories\Eloquent\MilestoneRepository;
use App\Repositories\Eloquent\ProjectRepository;
use App\Repositories\Eloquent\ProjectUserRepository;
use App\Repositories\Eloquent\TaskAssignmentRepository;
use App\Repositories\Eloquent\TaskRepository;
use App\Services\TaskProgressService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(MilestoneRepositoryInterface::class, MilestoneRepository::class);
        $this->app->bind(ProjectUserRepositoryInterface::class, ProjectUserRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(TaskAssignmentRepositoryInterface::class, TaskAssignmentRepository::class);
        $this->app->singleton(TaskProgressService::class);
    }

    public function boot(): void
    {
        $this->configurePolicies();
        $this->configureEvents();
        $this->configureRateLimiting();
        $this->configureDefaults();
    }

    private function configurePolicies(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Milestone::class, MilestonePolicy::class);
        Gate::policy(TaskAssignment::class, TaskAssignmentPolicy::class);

        Gate::define('viewDashboard', fn (User $user) => true);
    }

    private function configureEvents(): void
    {
        Event::listen(TaskCreated::class, LogTaskActivity::class);
        Event::listen(TaskUpdated::class, LogTaskActivity::class);
        Event::listen(TaskCompleted::class, LogTaskActivity::class);
        Event::listen(TaskCompleted::class, RefreshProjectStatus::class);

        Event::listen(ProjectCreated::class, LogProjectActivity::class);
        Event::listen(ProjectUpdated::class, LogProjectActivity::class);

        Event::listen(MilestoneCreated::class, LogMilestoneActivity::class);
        Event::listen(MilestoneUpdated::class, LogMilestoneActivity::class);
        Event::listen(MilestoneDeleted::class, LogMilestoneActivity::class);
        Event::listen(MilestoneRestored::class, LogMilestoneActivity::class);

        Event::listen(ProjectUserAssigned::class, LogProjectUserActivity::class);
        Event::listen(ProjectUserRemoved::class, LogProjectUserActivity::class);
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function ($request) {
            $user = $request->user();

            return Limit::perMinute(60)->by($user?->id ?: $request->ip());
        });
    }

    private function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
                ? Password::min(12)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : null,
        );
    }
}
