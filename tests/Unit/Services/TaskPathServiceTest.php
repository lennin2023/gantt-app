<?php

use App\Enums\TaskTypeEnum;
use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskPathService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['role_id' => 1]);
    $this->project = Project::factory()->create([
        'company_id' => $this->company->id,
        'created_by' => $this->user->id,
    ]);

    $this->service = app(TaskPathService::class);
});

// ─── buildPathForNewTask() ─────────────────────────────────────────────────

it('generates segment 0001 for first root task', function () {
    $path = $this->service->buildPathForNewTask(null);

    expect($path)->toBe('0001');
});

it('generates segment 0002 for second root task', function () {
    Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'path' => '0001',
        'created_by' => $this->user->id,
    ]);

    $path = $this->service->buildPathForNewTask(null);

    expect($path)->toBe('0002');
});

it('generates nested path for child task', function () {
    $parent = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'path' => '0001',
        'created_by' => $this->user->id,
    ]);

    $path = $this->service->buildPathForNewTask($parent->id);

    expect($path)->toBe('0001/0001');
});

it('excludes task from segment count', function () {
    $parent = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'path' => '0001',
        'created_by' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $parent->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0001/0001',
        'created_by' => $this->user->id,
    ]);

    $path = $this->service->buildPathForNewTask($parent->id, $task->id);

    expect($path)->toBe('0001/0001');
});

// ─── applyPathOnCreate() ───────────────────────────────────────────────────

it('saves correct path for root task', function () {
    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'path' => '0000',
        'created_by' => $this->user->id,
    ]);

    $this->service->applyPathOnCreate($task);

    expect($task->fresh()->path)->toBe('0001');
});

it('saves correct path for nested task', function () {
    $parent = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'path' => '0001',
        'created_by' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $parent->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0001/0000',
        'created_by' => $this->user->id,
    ]);

    $this->service->applyPathOnCreate($task);

    expect($task->fresh()->path)->toBe('0001/0001');
});

// ─── handleParentChange() ──────────────────────────────────────────────────

it('updates task path when moving to new parent', function () {
    $oldParent = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'path' => '0001',
        'created_by' => $this->user->id,
    ]);

    $newParent = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'path' => '0002',
        'created_by' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $oldParent->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0001/0001',
        'created_by' => $this->user->id,
    ]);

    $task->parent_id = $newParent->id;
    $this->service->handleParentChange($task, $oldParent->id, '0001/0001');

    expect($task->fresh()->path)->toBe('0002/0001');
});

it('updates task path when moving to root', function () {
    $oldParent = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $oldParent->id,
        'type' => TaskTypeEnum::TASK->value,
        'created_by' => $this->user->id,
    ]);

    $oldPath = $task->path;
    $task->parent_id = null;
    $this->service->handleParentChange($task, $oldParent->id, $oldPath);

    expect($task->fresh()->path)->toBe('0002');
});

it('updates descendant paths when parent changes', function () {
    $oldParent = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'path' => '0001',
        'created_by' => $this->user->id,
    ]);

    $newParent = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'path' => '0002',
        'created_by' => $this->user->id,
    ]);

    $child = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $oldParent->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0001/0001',
        'created_by' => $this->user->id,
    ]);

    $grandchild = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $child->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0001/0001/0001',
        'created_by' => $this->user->id,
    ]);

    $child->parent_id = $newParent->id;
    $this->service->handleParentChange($child, $oldParent->id, '0001/0001');

    expect($child->fresh()->path)->toBe('0002/0001')
        ->and($grandchild->fresh()->path)->toBe('0002/0001/0001');
});

it('renumbers siblings after task moves away', function () {
    $parent = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'path' => '0001',
        'created_by' => $this->user->id,
    ]);

    $task1 = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $parent->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0001/0001',
        'created_by' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $parent->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0001/0002',
        'created_by' => $this->user->id,
    ]);

    $task3 = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $parent->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0001/0003',
        'created_by' => $this->user->id,
    ]);

    $task2->parent_id = null;
    $this->service->handleParentChange($task2, $parent->id, '0001/0002');

    $siblings = Task::where('parent_id', $parent->id)->orderBy('path')->get();

    expect($siblings->pluck('path')->toArray())->toBe(['0001/0001', '0001/0002']);
});

// ─── updateDescendantPaths() ───────────────────────────────────────────────

it('updates all descendant paths', function () {
    $parent = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $child = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $parent->id,
        'type' => TaskTypeEnum::TASK->value,
        'created_by' => $this->user->id,
    ]);

    $grandchild = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $child->id,
        'type' => TaskTypeEnum::TASK->value,
        'created_by' => $this->user->id,
    ]);

    $this->service->updateDescendantPaths('0001', '0002');

    expect($child->fresh()->path)->toBe('0002/0001')
        ->and($grandchild->fresh()->path)->toBe('0002/0002/0001');
});

it('does not affect unrelated paths', function () {
    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::TASK->value,
        'created_by' => $this->user->id,
    ]);

    $this->service->updateDescendantPaths('9999', '8888');

    expect($task->fresh()->path)->not->toBe('8888');
});

// ─── renumberSiblings() ────────────────────────────────────────────────────

it('renumbers siblings sequentially', function () {
    $parent = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'path' => '0001',
        'created_by' => $this->user->id,
    ]);

    $task1 = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $parent->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0001/0003',
        'created_by' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $parent->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0001/0001',
        'created_by' => $this->user->id,
    ]);

    $task3 = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $parent->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0001/0002',
        'created_by' => $this->user->id,
    ]);

    $this->service->renumberSiblings($parent->id);

    $siblings = Task::where('parent_id', $parent->id)->orderBy('path')->get();

    expect($siblings->pluck('path')->toArray())->toBe(['0001/0001', '0001/0002', '0001/0003']);
});

it('handles root-level siblings', function () {
    $task1 = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0003',
        'created_by' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0001',
        'created_by' => $this->user->id,
    ]);

    $task3 = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::TASK->value,
        'path' => '0002',
        'created_by' => $this->user->id,
    ]);

    $this->service->renumberSiblings(null);

    $roots = Task::whereNull('parent_id')->orderBy('path')->get();

    expect($roots->pluck('path')->toArray())->toBe(['0001', '0002', '0003']);
});

// ─── Deep hierarchy ────────────────────────────────────────────────────────

it('handles 4-level deep hierarchy', function () {
    $l1 = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $l2 = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $l1->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $l3 = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $l2->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $l4 = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $l3->id,
        'type' => TaskTypeEnum::TASK->value,
        'created_by' => $this->user->id,
    ]);

    $this->service->updateDescendantPaths('0001/0001', '0002/0001');

    expect($l2->fresh()->path)->toBe('0001/0001')
        ->and($l3->fresh()->path)->toBe('0002/0001/0001')
        ->and($l4->fresh()->path)->toBe('0002/0001/0001/0001');
});
