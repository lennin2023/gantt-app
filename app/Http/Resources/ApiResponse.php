<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success($data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [];

        if ($data !== null) {
            $response['data'] = $data;

            if ($data instanceof LengthAwarePaginator) {
                $response['meta'] = [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                ];
            }
        }

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }

    protected function created($data = null, ?string $message = null): JsonResponse
    {
        return $this->success($data, $message ?? 'Created successfully', 201);
    }

    protected function deleted(string $message = 'Deleted successfully'): JsonResponse
    {
        return $this->success(null, $message, 200);
    }

    protected function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }

    protected function notFound(string $message = 'Not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function validationError(string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422);
    }
}
