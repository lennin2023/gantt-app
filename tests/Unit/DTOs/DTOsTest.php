<?php

use App\DTOs\BulkTaskDTO;
use App\DTOs\ProjectDTO;
use App\DTOs\TaskDTO;

// ─── TaskDTO ───────────────────────────────────────────────────────────────

it('creates TaskDTO from array with all fields', function () {
    $dto = TaskDTO::fromArray([
        'project_id' => 1,
        'type' => 'task',
        'parent_id' => 5,
        'task_status_id' => 2,
        'title' => 'Test task',
        'description' => 'Description',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'progress' => 50,
        'dependency_ids' => [1, 2],
        'dependency_type' => 'finish_to_start',
    ]);

    expect($dto->projectId)->toBe(1)
        ->and($dto->type)->toBe('task')
        ->and($dto->parentId)->toBe(5)
        ->and($dto->taskStatusId)->toBe(2)
        ->and($dto->title)->toBe('Test task')
        ->and($dto->description)->toBe('Description')
        ->and($dto->startDate)->toBe('2026-01-01')
        ->and($dto->endDate)->toBe('2026-12-31')
        ->and($dto->progress)->toBe(50)
        ->and($dto->dependencyIds)->toBe([1, 2])
        ->and($dto->dependencyType)->toBe('finish_to_start');
});

it('TaskDTO uses UNDEFINED for missing optional fields', function () {
    $dto = TaskDTO::fromArray([
        'project_id' => 1,
        'title' => 'Test',
    ]);

    expect($dto->description)->toBe(TaskDTO::UNDEFINED)
        ->and($dto->startDate)->toBe(TaskDTO::UNDEFINED)
        ->and($dto->endDate)->toBe(TaskDTO::UNDEFINED)
        ->and($dto->dependencyIds)->toBe(TaskDTO::UNDEFINED_ARRAY);
});

it('TaskDTO toArray excludes UNDEFINED fields', function () {
    $dto = TaskDTO::fromArray([
        'project_id' => 1,
        'title' => 'Test',
    ]);

    $array = $dto->toArray();

    expect($array)->toHaveKey('project_id')
        ->and($array)->toHaveKey('title')
        ->and($array)->not->toHaveKey('description')
        ->and($array)->not->toHaveKey('start_date')
        ->and($array)->not->toHaveKey('end_date');
});

it('TaskDTO toArray includes null when explicitly set', function () {
    $dto = TaskDTO::fromArray([
        'project_id' => 1,
        'title' => 'Test',
        'description' => null,
    ]);

    $array = $dto->toArray();

    expect($array)->toHaveKey('description')
        ->and($array['description'])->toBeNull();
});

it('TaskDTO toArray excludes empty dependency_ids array', function () {
    $dto = TaskDTO::fromArray([
        'project_id' => 1,
        'title' => 'Test',
        'dependency_ids' => [],
    ]);

    $array = $dto->toArray();

    expect($array)->not->toHaveKey('dependency_ids');
});

// ─── ProjectDTO ────────────────────────────────────────────────────────────

it('creates ProjectDTO from array with all fields', function () {
    $dto = ProjectDTO::fromArray([
        'company_id' => 1,
        'project_status_id' => 2,
        'name' => 'Test Project',
        'color' => '#3B82F6',
        'description' => 'Description',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    expect($dto->companyId)->toBe(1)
        ->and($dto->projectStatusId)->toBe(2)
        ->and($dto->name)->toBe('Test Project')
        ->and($dto->color)->toBe('#3B82F6')
        ->and($dto->description)->toBe('Description')
        ->and($dto->startDate)->toBe('2026-01-01')
        ->and($dto->endDate)->toBe('2026-12-31');
});

it('ProjectDTO uses UNDEFINED for missing fields', function () {
    $dto = ProjectDTO::fromArray([
        'name' => 'Test',
    ]);

    expect($dto->description)->toBe(ProjectDTO::UNDEFINED)
        ->and($dto->startDate)->toBe(ProjectDTO::UNDEFINED)
        ->and($dto->endDate)->toBe(ProjectDTO::UNDEFINED);
});

it('ProjectDTO toArray excludes UNDEFINED fields', function () {
    $dto = ProjectDTO::fromArray([
        'name' => 'Test',
        'color' => '#3B82F6',
    ]);

    $array = $dto->toArray();

    expect($array)->toHaveKey('name')
        ->and($array)->toHaveKey('color')
        ->and($array)->not->toHaveKey('description')
        ->and($array)->not->toHaveKey('start_date')
        ->and($array)->not->toHaveKey('end_date');
});

it('ProjectDTO toArray includes null when explicitly set', function () {
    $dto = ProjectDTO::fromArray([
        'name' => 'Test',
        'description' => null,
    ]);

    $array = $dto->toArray();

    expect($array)->toHaveKey('description')
        ->and($array['description'])->toBeNull();
});

// ─── BulkTaskDTO ───────────────────────────────────────────────────────────

it('creates BulkTaskDTO from array', function () {
    $dto = BulkTaskDTO::fromArray([
        'task_ids' => [1, 2, 3],
        'data' => [
            'task_status_id' => 3,
            'progress' => 100,
            'title' => 'Updated',
        ],
    ]);

    expect($dto->taskIds)->toBe([1, 2, 3])
        ->and($dto->taskStatusId)->toBe(3)
        ->and($dto->progress)->toBe(100)
        ->and($dto->title)->toBe('Updated');
});

it('BulkTaskDTO uses UNDEFINED for missing data fields', function () {
    $dto = BulkTaskDTO::fromArray([
        'task_ids' => [1],
        'data' => [],
    ]);

    expect($dto->taskStatusId)->toBe(BulkTaskDTO::UNDEFINED)
        ->and($dto->title)->toBe(BulkTaskDTO::UNDEFINED)
        ->and($dto->progress)->toBe(BulkTaskDTO::UNDEFINED);
});

it('BulkTaskDTO toArray excludes UNDEFINED fields', function () {
    $dto = BulkTaskDTO::fromArray([
        'task_ids' => [1],
        'data' => [
            'progress' => 50,
        ],
    ]);

    $array = $dto->toArray();

    expect($array)->toHaveKey('progress')
        ->and($array)->not->toHaveKey('task_status_id')
        ->and($array)->not->toHaveKey('title');
});

it('BulkTaskDTO toArray includes null when explicitly set', function () {
    $dto = BulkTaskDTO::fromArray([
        'task_ids' => [1],
        'data' => [
            'title' => null,
        ],
    ]);

    $array = $dto->toArray();

    expect($array)->toHaveKey('title')
        ->and($array['title'])->toBeNull();
});
