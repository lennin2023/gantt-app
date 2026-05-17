<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiResource extends JsonResource
{
    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'data' => $this->resource,
        ]);
    }
}
