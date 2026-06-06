<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'project_user' => $this->whenLoaded('projectUser', fn () => [
                'id' => $this->projectUser->id,
                'user' => [
                    'id' => $this->projectUser->user->id,
                    'name' => $this->projectUser->user->name,
                    'email' => $this->projectUser->user->email,
                ],
            ]),
            'task_role' => $this->whenLoaded('taskRole', fn () => $this->taskRole ? [
                'id' => $this->taskRole->id,
                'name' => $this->taskRole->name,
                'slug' => $this->taskRole->slug,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
