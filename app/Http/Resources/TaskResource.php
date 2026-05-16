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
            'project_id' => $this->project_id,
            'task_status_id' => $this->task_status_id,
            'status' => $this->whenLoaded('status', fn () => [
                'name' => $this->status->name,
                'slug' => $this->status->slug,
                'color' => $this->status->color,
            ]),
            'name' => $this->name,
            'description' => $this->description,
            'assignee' => $this->assignee,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'progress' => $this->progress,
            'order' => $this->order,
            'dependency_ids' => $this->when(
                $this->relationLoaded('dependencies'),
                fn () => $this->dependencies->pluck('id')->toArray()
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
