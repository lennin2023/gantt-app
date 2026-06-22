<?php

namespace App\Services;

use App\Models\Task;

class TaskPathService
{
    private const PAD_LENGTH = 4;

    public function buildPathForNewTask(?int $parentId, ?int $excludeTaskId = null): string
    {
        $parentPath = $parentId
            ? Task::findOrFail($parentId)->path
            : null;

        $segment = $this->nextSegment($parentId, $excludeTaskId);

        return $parentPath
            ? "{$parentPath}/{$segment}"
            : $segment;
    }

    public function applyPathOnCreate(Task $task): void
    {
        $task->path = $this->buildPathForNewTask($task->parent_id, $task->id);
        $task->saveQuietly();
    }

    public function handleParentChange(Task $task, ?int $oldParentId, string $oldPath): void
    {
        $newParentPath = $task->parent_id
            ? Task::findOrFail($task->parent_id)->path
            : null;

        $segment = $this->nextSegment($task->parent_id, $task->id);

        $newPath = $newParentPath
            ? "{$newParentPath}/{$segment}"
            : $segment;

        $task->path = $newPath;
        $task->saveQuietly();

        $this->updateDescendantPaths($oldPath, $newPath);
        $this->renumberSiblings($oldParentId);
    }

    public function updateDescendantPaths(string $oldPath, string $newPath): void
    {
        Task::where('path', 'LIKE', "{$oldPath}/%")
            ->each(function (Task $task) use ($oldPath, $newPath) {
                $task->path = str_replace($oldPath.'/', $newPath.'/', $task->path);
                $task->saveQuietly();
            });
    }

    public function renumberSiblings(?int $parentId): void
    {
        $siblings = Task::where('parent_id', $parentId)
            ->orderBy('path')
            ->get();

        $parentPath = $parentId ? Task::find($parentId)?->path : null;

        foreach ($siblings as $index => $sibling) {
            $segment = str_pad((string) ($index + 1), self::PAD_LENGTH, '0', STR_PAD_LEFT);
            $newPath = $parentPath ? "{$parentPath}/{$segment}" : $segment;

            if ($sibling->path !== $newPath) {
                $oldSiblingPath = $sibling->path;

                $sibling->path = $newPath;
                $sibling->saveQuietly();

                $this->updateDescendantPaths($oldSiblingPath, $newPath);
            }
        }
    }

    private function nextSegment(?int $parentId, ?int $excludeTaskId = null): string
    {
        $query = Task::where('parent_id', $parentId);

        if ($excludeTaskId !== null) {
            $query->where('id', '!=', $excludeTaskId);
        }

        $count = $query->count();

        return str_pad((string) ($count + 1), self::PAD_LENGTH, '0', STR_PAD_LEFT);
    }
}
