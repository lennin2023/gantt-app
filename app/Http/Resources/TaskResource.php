<?php

namespace App\Http\Resources;

use App\Enums\TaskTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $base = [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'parent_id' => $this->parent_id,
            'path' => $this->path,
            'type' => $this->type,
            'task_status_id' => $this->task_status_id,
            'status' => $this->whenLoaded('status', fn () => [
                'id' => $this->status->id,
                'name' => $this->status->name,
                'slug' => $this->status->slug,
                'color' => $this->status->color,
            ]),
            'title' => $this->title,
            'order' => $this->order,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'dependencies' => $this->whenLoaded('dependencies', fn () => $this->dependencies->map(fn ($dep) => [
                'id' => $dep->id,
                'type' => $dep->pivot->type,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'updater' => $this->whenLoaded('updater', fn () => [
                'id' => $this->updater->id,
                'name' => $this->updater->name,
            ]),
        ];

        // Campos exclusivos de task
        if ($this->type === TaskTypeEnum::TASK) {
            $base['description'] = $this->description;
            $base['progress'] = $this->progress;
            $base['assignments'] = $this->whenLoaded('assignments', fn () => $this->assignments->map(fn ($assignment) => [
                'id' => $assignment->id,
                'user' => [
                    'id' => $assignment->projectUser->user->id,
                    'name' => $assignment->projectUser->user->name,
                ],
                'task_role' => $assignment->taskRole ? [
                    'id' => $assignment->taskRole->id,
                    'name' => $assignment->taskRole->name,
                    'slug' => $assignment->taskRole->slug,
                ] : null,
            ]));
        }

        // Campos exclusivos de container
        if ($this->type === TaskTypeEnum::CONTAINER) {
            $base['progress'] = $this->progress;
            $base['children'] = $this->whenLoaded('children', fn () => TaskResource::collection($this->children));
        }

        return $base;
    }
}
