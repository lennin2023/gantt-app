<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MilestoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'date' => $this->date->toDateString(),
            'reached' => $this->reached,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
