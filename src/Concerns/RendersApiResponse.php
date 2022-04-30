<?php

namespace KennedyOsaze\LaravelApiResponse\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Validation\ValidationException;
use KennedyOsaze\LaravelApiResponse\ApiResponse;
use Throwable;

trait RendersApiResponse
{
    public function okResponse(string $message, $data = null, array $headers = []): JsonResponse
    {
        return $this->successResponse($message, $data, headers: $headers);
    }

    public function createdResponse(string $message, $data = null, array $headers = []): JsonResponse
    {
        return $this->successResponse($message, $data, 201, $headers);
    }

    public function acceptedResponse(string $message, $data = null, array $headers = []): JsonResponse
    {
        return $this->successResponse($message, $data, 202, $headers);
    }

    public function noContentResponse(): JsonResponse
    {
        return $this->successResponse('', null, 204);
    }

    public function successResponse(string $message, $data = null, int $status = 200, array $headers = []): JsonResponse
    {
        return ApiResponse::create($status, $message, $data, $headers);
    }

    public function resourceResponse(JsonResource $resource, string $message, int $status = 200, array $headers = []): JsonResponse
    {
        if (! $resource instanceof ResourceCollection && blank($resource->with) && blank($resource->additional)) {
            return ApiResponse::create($status, $message, $resource, $headers);
        }

        $response = $resource->response()->withHeaders($headers)->setStatusCode($status);

        return ApiResponse::fromJsonResponse($response, $message, true);
    }

    public function resourceCollectionResponse(
        ResourceCollection $collection, string $message, bool $wrap = true, int $status = 200, array $headers = []
    ): JsonResponse {
        $response = $collection->response()->withHeaders($headers)->setStatusCode($status);

        return ApiResponse::fromJsonResponse($response, $message, $wrap);
    }

    public function unauthenticatedResponse(string $message): JsonResponse
    {
        return $this->clientErrorResponse($message, 401);
    }

    public function badRequestResponse(string $message, ?array $error = null): JsonResponse
    {
        return $this->clientErrorResponse($message, 400, $error);
    }

    public function forbiddenResponse(string $message, ?array $error = null): JsonResponse
    {
        return $this->clientErrorResponse($message, 403, $error);
    }

    public function notFoundResponse(string $message, ?array $error = null): JsonResponse
    {
        return $this->clientErrorResponse($message, 404, $error);
    }

    public function throwValidationExceptionWhen($condition, array $messages): void
    {
        if ((bool) $condition) {
            throw ValidationException::withMessages($messages);
        }
    }

    public function validationFailedResponse(Validator $validator, ?Request $request = null, ?string $message = null): JsonResponse
    {
        return ApiResponse::fromFailedValidation($validator, $request ?? request(), $message);
    }

    public function clientErrorResponse(string $message, int $status = 400, ?array $error = null, array $headers = []): JsonResponse
    {
        return ApiResponse::create($status, $message, $error, $headers);
    }

    public function serverErrorResponse(string $message, int $status = 500, ?Throwable $exception = null): JsonResponse
    {
        if ($exception !== null) {
            report($exception);
        }

        return ApiResponse::create($status, $message ?: $exception?->getMessage());
    }
}
