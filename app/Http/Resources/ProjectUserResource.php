<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'project_role' => $this->whenLoaded('projectRole', fn () => [
                'id' => $this->projectRole->id,
                'name' => $this->projectRole->name,
                'slug' => $this->projectRole->slug,
                'level' => $this->projectRole->level,
            ]),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
