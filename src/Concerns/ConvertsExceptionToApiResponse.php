<?php

namespace KennedyOsaze\LaravelApiResponse\Concerns;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use KennedyOsaze\LaravelApiResponse\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

trait ConvertsExceptionToApiResponse
{
    public function renderApiResponse(Throwable $exception, Request $request): JsonResponse|Response
    {
        $exception = $this->prepareApiException($exception, $request);

        if ($response = $this->getExceptionResponse($exception, $request)) {
            return $response;
        }

        if ($exception instanceof HttpException) {
            return $this->convertHttpExceptionToJsonResponse($exception);
        }

        if ($this->shouldRenderHtmlOnException()) {
            return parent::render($request, $exception);
        }

        return ApiResponse::create(500, 'Server Error', $this->convertExceptionToArray($exception));
    }

    protected function prepareApiException(Throwable $e, Request $request): Throwable
    {
        return match (true) {
            $e instanceof NotFoundHttpException, $e instanceof ModelNotFoundException => with(
                $e, function ($e) {
                    $message = (string) with($e->getMessage(), function ($message) {
                        return blank($message) || Str::contains($message, 'No query results for model') ? 'Resource not found.' : $message;
                    });

                    return new NotFoundHttpException($message, $e);
                }
            ),
            $e instanceof AuthenticationException => new HttpException(401, $e->getMessage(), $e),
            $e instanceof UnauthorizedException => new HttpException(403, $e->getMessage(), $e),
            default => $e,
        };
    }

    protected function getExceptionResponse(Throwable $exception, Request $request): ?JsonResponse
    {
        if ($exception instanceof HttpResponseException) {
            $response = $exception->getResponse();

            return $response instanceof JsonResponse
                ? ApiResponse::fromJsonResponse($response, 'An error occurred')
                : ApiResponse::create($response->getStatusCode(), 'An error occurred', ['content' => $response->getContent()]);
        }

        if ($exception instanceof ValidationException) {
            return ApiResponse::fromFailedValidation($exception->validator, $request);
        }

        return null;
    }

    protected function convertHttpExceptionToJsonResponse(HttpExceptionInterface $exception): JsonResponse
    {
        $statusCode = $exception->getStatusCode();
        $message = $exception->getMessage() ?: JsonResponse::$statusTexts[$statusCode];
        $headers = $exception->getHeaders();
        $data = method_exists($exception, 'getErrorData') ? call_user_func([$exception, 'getErrorData']) : null;

        return ApiResponse::create($statusCode, $message, $data, $headers);
    }

    protected function shouldRenderHtmlOnException(): bool
    {
        return (bool) config('api-response.render_html_on_exception');
    }
}
