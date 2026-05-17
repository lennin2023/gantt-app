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
            'status' => $this->whenLoaded('status'),
            'name' => $this->name,
            'description' => $this->description,
            'assignee' => $this->whenLoaded('projectUser'),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'progress' => $this->progress,
            'order' => $this->order,
            'dependency_ids' => $this->whenLoaded('dependencies', fn () => $this->dependencies->pluck('id')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
