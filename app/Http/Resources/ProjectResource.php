<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'project_status_id' => $this->project_status_id,
            'status' => $this->whenLoaded('status', fn () => [
                'name' => $this->status->name,
                'slug' => $this->status->slug,
                'color' => $this->status->color,
            ]),
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'project_users' => ProjectUserResource::collection($this->whenLoaded('projectUsers')),
        ];
    }
}
