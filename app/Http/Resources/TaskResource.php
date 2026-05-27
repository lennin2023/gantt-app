<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_user_id' => $this->project_user_id,
            'task_status_id' => $this->task_status_id,
            'status' => $this->whenLoaded('status', fn () => [
                'id' => $this->status->id,
                'name' => $this->status->name,
                'slug' => $this->status->slug,
                'color' => $this->status->color,
            ]),
            'name' => $this->name,
            'description' => $this->description,
            'assignee' => $this->whenLoaded('projectUser', fn () => [
                'id' => $this->projectUser->id,
                'user' => [
                    'id' => $this->projectUser->user->id,
                    'name' => $this->projectUser->user->name,
                ],
            ]),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'progress' => $this->progress,
            'order' => $this->order,
            'dependency_ids' => $this->whenLoaded('dependencies', fn () => $this->dependencies->pluck('id')),
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
    }
}
