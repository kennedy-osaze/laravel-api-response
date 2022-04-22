<?php

namespace KennedyOsaze\LaravelApiResponse;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;
use JsonSerializable;
use KennedyOsaze\LaravelApiResponse\Concerns\Translatable;
use stdClass;

class ApiResponse
{
    use Conditionable, Translatable;

    protected $data;

    protected ?string $message;

    protected int $statusCode;

    protected array $headers;

    protected bool $shouldWrapResponse = true;

    public static $formatValidationCallback;

    public function __construct(int $statusCode, string $message = null, $data = null, array $headers = [])
    {
        $this->message = $message;
        $this->data = $data;
        $this->headers = $headers;

        $this->setStatusCode($statusCode);
    }

    public static function create(int $statusCode, string $message = null, $data = null, array $headers = []): JsonResponse
    {
        return (new static($statusCode, $message, $data, $headers))->make();
    }

    public static function fromJsonResponse(JsonResponse $response, string $message = null, bool $wrap = false): JsonResponse
    {
        $data = $response->getData(true);

        $responseData = is_array($data) ? $data : ['message_data' => $data];
        $message = (string) ($message ?: Arr::pull($responseData, 'message', ''));

        $response = new static($response->status(), $message, $responseData, $response->headers->all());

        return $response->unless($wrap, fn (self $response) => $response->ignoreDataWrapper())->make();
    }

    public static function fromFailedValidation(Validator $validator, ?Request $request = null, ?string $message = null): JsonResponse
    {
        ['code' => $code, 'message' => $defaultMessage] = config('api-response.validation');

        $response = new static($code, $message ?? $defaultMessage);

        $errors = $response->getValidationErrors($validator, $request ?? request());

        return $response->setData($errors)->make();
    }

    protected function make(): JsonResponse
    {
        $statusesWithNoContent = config('api-response.http_statuses_with_no_content');

        $data = in_array($this->statusCode, $statusesWithNoContent) ? null : $this->prepareResponseData();

        return new JsonResponse($data, $this->statusCode, $this->headers);
    }

    public function getValidationErrors(Validator $validator, Request $request): array
    {
        if (is_callable(static::$formatValidationCallback)) {
            return call_user_func_array(static::$formatValidationCallback, [$validator, $request]);
        }

        $normalizedMessages = array_unique(
            Arr::dot($validator->errors()->getMessages())
        );

        $result = new Collection([]);

        collect($normalizedMessages)->each(function ($message, $key) use (&$result, $request) {
            $field = Str::before($key, '.');

            if (! $result->has($field)) {
                $result = $result->put($field, [
                    'message' => $message,
                    'rejected_value' => $request->input($field),
                ]);
            }
        });

        return $result->all();
    }

    protected function prepareResponseData(): ?array
    {
        $successful = $this->statusCode >= 200 && $this->statusCode < 300;

        $normalizedData = $this->normalizeData($this->data);
        $data = is_array($normalizedData) ? $normalizedData : [];

        $messageData = $this->getTranslatedMessageMeta($this->message, $data, $successful);
        $normalizedData = is_array($normalizedData) ? $data : $normalizedData;

        $responseData = ['success' => $successful, 'message' => $messageData['message']];
        $responseData += Arr::except($messageData, ['key', 'message']);

        if ($this->shouldWrapResponse && filled($normalizedData)) {
            $responseData[$this->getDataWrapper()] = $normalizedData;
        } elseif (! is_null($normalizedData)) {
            $responseData += $normalizedData;
        }

        return $responseData;
    }

    private function getTranslatedMessageMeta(string $message, array &$data, bool $successful): array
    {
        $fileKey = $successful ? 'success' : 'errors';
        $translationFilename = config("api-response.translation.{$fileKey}");

        $translated = $this->extractTranslationDataFromResponsePayload($data, $message, $translationFilename);

        if ($successful) {
            return $translated;
        }

        return array_merge($translated, $this->pullErrorCodeFromData($data, $message, $translated['key']));
    }

    private function extractTranslationDataFromResponsePayload(array &$data, string $message, string $prefix)
    {
        $parameters = $this->parseStringToTranslationParameters($message);

        $attributes = array_merge($parameters['attributes'], Arr::pull($data, '_attributes', []));

        return $this->getTranslatedStringArray($parameters['name'], $attributes, $prefix);
    }

    private function pullErrorCodeFromData(array &$data, string $message, ?string $translatedKey = null): array
    {
        if (array_key_exists('error_code', $data)) {
            return ['error_code' => (string) Arr::pull($data, 'error_code')];
        }

        if (! is_null($translatedKey) && Str::contains($message, 'error_code.')) {
            return ['error_code' => $translatedKey];
        }

        return [];
    }

    public function setStatusCode(int $statusCode): void
    {
        if (! array_key_exists($statusCode, JsonResponse::$statusTexts)) {
            throw new InvalidArgumentException("Invalid HTTP status code: [{$statusCode}]");
        }

        $this->statusCode = $statusCode;
    }

    public function setData($data): static
    {
        return tap($this, fn (self $response) => $response->data = $data);
    }

    public function ignoreDataWrapper(): static
    {
        return tap($this, fn (self $response) => $response->shouldWrapResponse = false);
    }

    protected function normalizeData($data): ?array
    {
        if (is_array($data) || is_null($data)) {
            return $data;
        }

        return match (true) {
            $data instanceof JsonResource => $data->resolve(),
            $data instanceof Jsonable => json_decode($data->toJson(), true),
            $data instanceof JsonSerializable => $data->jsonSerialize(),
            $data instanceof Arrayable => $data->toArray(),
            $data instanceof stdClass => (array) $data,
            default => $data
        };
    }

    private function getDataWrapper(): ?string
    {
        if (! $this->shouldWrapResponse) {
            return null;
        }

        return collect(config('api-response.data_wrappers'))->first(fn ($value, $key) => Str::is(Str::of($key)->replace('x', '*'), $this->statusCode)
        );
    }
}
