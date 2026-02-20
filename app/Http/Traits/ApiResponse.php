<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse(
        string $type,
        string $description,
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => $type,
            'actionStatus' => 'CompletedActionStatus',
            'description' => $description,
        ], $statusCode);
    }

    protected function successWithResult(
        string $type,
        string $description,
        mixed $result,
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => $type,
            'actionStatus' => 'CompletedActionStatus',
            'description' => $description,
            'result' => $result,
        ], $statusCode);
    }

    protected function errorResponse(
        string $error,
        int $statusCode = 400
    ): JsonResponse {
        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'Action',
            'actionStatus' => 'FailedActionStatus',
            'error' => $error,
        ], $statusCode);
    }

    protected function resourceResponse(
        mixed $resource,
        int $statusCode = 200
    ): JsonResponse {
        return response()->json($resource, $statusCode);
    }
}
