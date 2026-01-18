<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponse
{
    /**
     * Return a success JSON response.
     */
    protected function success(
        mixed $data = null,
        string $message = null,
        int $code = 200
    ): JsonResponse {
        $response = [];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a created JSON response (201).
     */
    protected function created(
        mixed $data = null,
        string $message = 'Created successfully'
    ): JsonResponse {
        return $this->success($data, $message, 201);
    }

    /**
     * Return a resource response with additional message.
     */
    protected function resource(
        JsonResource $resource,
        string $message = null,
        int $code = 200
    ): JsonResponse {
        $additional = $message ? ['message' => $message] : [];

        return $resource
            ->additional($additional)
            ->response()
            ->setStatusCode($code);
    }

    /**
     * Return a collection response with additional message.
     */
    protected function collection(
        ResourceCollection $collection,
        string $message = null
    ): ResourceCollection {
        if ($message) {
            return $collection->additional(['message' => $message]);
        }

        return $collection;
    }

    /**
     * Return a no content response (204).
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return an error JSON response.
     */
    protected function error(
        string $message,
        int $code = 400,
        array $errors = []
    ): JsonResponse {
        $response = ['message' => $message];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a not found response (404).
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Return an unauthorized response (401).
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Return a forbidden response (403).
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Return a validation error response (422).
     */
    protected function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->error($message, 422, $errors);
    }
}
