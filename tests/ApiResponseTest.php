<?php

namespace KennedyOsaze\LaravelApiResponse\Tests;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use JsonSerializable;
use KennedyOsaze\LaravelApiResponse\ApiResponse;
use KennedyOsaze\LaravelApiResponse\Tests\Fakes\DummyModel;
use KennedyOsaze\LaravelApiResponse\Tests\Fakes\DummyResource;
use KennedyOsaze\LaravelApiResponse\Tests\Fakes\DummyResourceCollection;

class ApiResponseTest extends TestCase
{
    public function testExceptionThrownWithInvalidStatusCode()
    {
        $this->expectException(InvalidArgumentException::class);

        ApiResponse::create(1000);
    }

    public function testCreateReturnsResponseWithAppropriateData()
    {
        $successfulResponse = ApiResponse::create(200, 'A successful message');
        $errorResponse = ApiResponse::create(400, 'An error message');

        $this->assertInstanceOf(JsonResponse::class, $successfulResponse);
        $this->assertInstanceOf(JsonResponse::class, $errorResponse);
        $this->assertSame($successfulResponse->status(), 200);
        $this->assertSame($errorResponse->status(), 400);

        $this->assertSame($successfulResponse->getData(true), [
            'success' => true,
            'message' => 'A successful message',
        ]);

        $this->assertSame($errorResponse->getData(true), [
            'success' => false,
            'message' => 'An error message',
        ]);
    }

    public function testCreateResponseHasSpecifiedHeaders()
    {
        $response = ApiResponse::create(200, 'A successful message', headers: ['X-Dummy-Header' => 'Dummy Message']);

        $this->assertTrue($response->headers->has('X-Dummy-Header'));
        $this->assertSame($response->headers->get('X-Dummy-Header'), 'Dummy Message');
    }

    public function testResponseWithNoContent()
    {
        $response = ApiResponse::create(204);

        $this->assertSame(204, $response->status());
        $this->assertSame('{}', $response->getContent());
    }

    public function testResponseWithArrayData()
    {
        $successData = ['name' => 'Kennedy Osaze'];
        $errorData = ['error' => 'Oops!'];

        $successfulResponseData = ApiResponse::create(200, 'A dummy message', $successData)->getData(true);
        $errorResponseData = ApiResponse::create(400, 'Bad error', $errorData)->getData(true);

        $this->assertArrayHasKey('data', $successfulResponseData);
        $this->assertArrayNotHasKey('error', $successfulResponseData);
        $this->assertArrayHasKey('error', $errorResponseData);
        $this->assertArrayNotHasKey('data', $errorResponseData);

        $this->assertSame($successData, $successfulResponseData['data']);
        $this->assertSame($errorData, $errorResponseData['error']);
    }

    public function testResponseWithEmptyArrayDataDoesNotHaveAnyDataKey()
    {
        $response = ApiResponse::create(200, 'A dummy message', []);

        $this->assertArrayNotHasKey('data', $response->getData(true));
    }

    public function testResponseWithArrayableData()
    {
        $object = new class() implements Arrayable {
            public function toArray()
            {
                return ['foo' => 'bar'];
            }
        };

        $responseData = ApiResponse::create(200, 'A dummy message', $object)->getData(true);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertSame($responseData['data'], ['foo' => 'bar']);
    }

    public function testResponseWithJsonableData()
    {
        $object = new class() implements Jsonable {
            public function toJson($options = 0)
            {
                return json_encode(['foo' => 'bar'], $options);
            }
        };

        $responseData = ApiResponse::create(200, 'A dummy message', $object)->getData(true);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertSame($responseData['data'], ['foo' => 'bar']);
    }

    public function testResponseWithJsonSerializableData()
    {
        $object = new class() implements JsonSerializable {
            public function jsonSerialize()
            {
                return ['foo' => 'bar'];
            }
        };

        $responseData = ApiResponse::create(200, 'A dummy message', $object)->getData(true);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertSame($responseData['data'], ['foo' => 'bar']);
    }

    public function testCorrectResponseReturnedOnJsonResponse()
    {
        $data = ['user' => ['name' => 'Kennedy']];

        $responseA = new JsonResponse($data, 200, ['x-dummy' => 'Test']);
        $responseJsonA = ApiResponse::fromJsonResponse($responseA);

        $responseB = new JsonResponse(['message' => 'Dummy Message'] + $data, 200);
        $responseDataB = ApiResponse::fromJsonResponse($responseB)->getData(true);

        $responseC = new JsonResponse($data, 200);
        $responseDataC = ApiResponse::fromJsonResponse($responseC, 'Dummy Message')->getData(true);

        $responseD = new JsonResponse($data, 200);
        $responseDataD = ApiResponse::fromJsonResponse($responseD, 'Dummy Message', true)->getData(true);

        $responseData = ['success' => true, 'user' => ['name' => 'Kennedy']];

        $this->assertSame(['success' => true, 'message' => 'OK'] + $data, $responseJsonA->getData(true));
        $this->assertSame($responseJsonA->headers->get('x-dummy'), 'Test');
        $this->assertSame(['success' => true, 'message' => 'Dummy Message'] + $data, $responseDataB);
        $this->assertSame(['success' => true, 'message' => 'Dummy Message'] + $data, $responseDataC);
        $this->assertSame(['success' => true, 'message' => 'Dummy Message'] + compact('data'), $responseDataD);
    }

    public function testCorrectResponseReturnedOnJsonResource()
    {
        DummyModel::migrate();

        $model = DummyModel::create(['name' => 'Kennedy']);
        DummyModel::insert(Collection::times(5, fn ($number) => [
            'name' => "Kennedy:{$number}", 'created_at' => now(), 'updated_at' => now(),
        ])->toArray());

        $resource = new DummyResource($model);
        $collection = DummyResource::collection(DummyModel::all());

        $responseDataA = ApiResponse::create(201, 'Dummy Model Created', $resource)->getData(true);
        $responseDataB = ApiResponse::fromJsonResponse($resource->response(), 'Dummy Model Created')->getData(true);
        $responseDataC = ApiResponse::fromJsonResponse($resource->response(), 'Dummy Model Created', true)->getData(true);
        $responseDataD = ApiResponse::fromJsonResponse($collection->response(), 'List of Dummy Models')->getData(true);

        $this->assertSame([
            'success' => true,
            'message' => 'Dummy Model Created',
            'data' => $resource->resolve(),
        ], $responseDataA);
        $this->assertSame($responseDataA, $responseDataB);
        $this->assertSame([
            'success' => true,
            'message' => 'Dummy Model Created',
            'data' => ['data' => $resource->toArray(request())],
        ], $responseDataC);
        $this->assertCount(6, $responseDataD['data']);
    }

    public function testCorrectResponseReturnedOnResourceCollection()
    {
        DummyModel::migrate();

        DummyModel::insert(Collection::times(5, fn ($number) => [
            'name' => "Kennedy:{$number}", 'created_at' => now(), 'updated_at' => now(),
        ])->toArray());

        $collectionA = new DummyResourceCollection(DummyModel::all());
        $responseDataA = ApiResponse::fromJsonResponse($collectionA->response(), 'List of Dummy Models')->getData(true);

        $collectionB = (new DummyResourceCollection(DummyModel::paginate(2)))->setWrapper('result');
        $responseDataB = ApiResponse::fromJsonResponse($collectionB->response(), 'List of Dummy Models Paginated', true)->getData(true);

        $collectionC = (new DummyResourceCollection(DummyModel::paginate(2)))->setWrapper(null);
        $responseDataC = ApiResponse::fromJsonResponse($collectionC->response(), 'List of Dummy Models Paginated', true)->getData(true);

        $collectionD = (new DummyResourceCollection(DummyModel::paginate(2)))->setWrapper('data')->useParentToArray(false);
        $responseDataD = ApiResponse::fromJsonResponse($collectionD->response(), 'List of Dummy Models Paginated', true)->getData(true);

        $collectionE = (new DummyResourceCollection(DummyModel::paginate(2)))->setWrapper(null)->useParentToArray(false);
        $responseDataE = ApiResponse::fromJsonResponse($collectionE->response(), 'List of Dummy Models Paginated', true)->getData(true);

        $this->assertSame($collectionA->resolve(), $responseDataA['data']);

        collect(['result', 'links', 'meta'])->each(fn ($key) => $this->assertArrayHasKey($key, $responseDataB['data']));
        $this->assertSame($collectionB->toArray(request()), $responseDataB['data']['result']);

        collect(['data', 'links', 'meta'])->each(fn ($key) => $this->assertArrayHasKey($key, $responseDataC['data']));
        $this->assertSame($collectionC->toArray(request()), $responseDataC['data']['data']);

        collect(['data', 'link', 'links', 'meta'])->each(fn ($key) => $this->assertArrayHasKey($key, $responseDataD['data']));
        $this->assertSame($collectionD->collection->map->resolve()->toArray(), $responseDataD['data']['data']);

        collect(['data', 'links', 'meta'])->each(fn ($key) => $this->assertArrayHasKey($key, $responseDataE['data']));
        $this->assertSame($collectionE->collection->map->resolve()->toArray(), $responseDataE['data']['data']['data']);
    }

    /**
     * @dataProvider getRandomDataTypeProvider
     */
    public function testResponseWithRandomDataTypeContent($input, $result)
    {
        $responseData = ApiResponse::create(200, 'A dummy message', $input)->getData(true);

        $this->assertSame($result, $responseData['data']);
    }

    public function getRandomDataTypeProvider()
    {
        return [
            'test object' => [(object) ['type' => 'string'], ['type' => 'string']],
            'test string' => ['string', 'string'],
            'test integer' => [1, 1],
            'test boolean' => [true, true],
            'test collection' => [
                new Collection([
                    ['big_cap' => 'A', 'small_cap' => 'a'],
                    ['big_cap' => 'B', 'small_cap' => 'b'],
                ]),
                [
                    ['big_cap' => 'A', 'small_cap' => 'a'],
                    ['big_cap' => 'B', 'small_cap' => 'b'],
                ],
            ],
        ];
    }

    public function testCorrectResponseWithDataWrapperIgnored()
    {
        $response = (new ApiResponse(200, 'Dummy Message', ['name' => 'Kennedy']))
            ->ignoreDataWrapper()
            ->make();

        $this->assertSame([
            'success' => true,
            'message' => 'Dummy Message',
            'name' => 'Kennedy',
        ], $response->getData(true));
    }

    public function testFailedBasicValidationReturnsAppropriateResponseData()
    {
        $validator = Validator::make(['name' => null], ['name' => 'required', 'age' => 'required']);

        $errors = $validator->errors();

        $response = ApiResponse::fromFailedValidation($validator);
        $responseData = $response->getData(true);

        $this->assertFalse($responseData['success']);
        $this->assertSame($response->status(), 422);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertTrue(count($responseData['errors']) === count($errors->messages()));
        $this->assertContains([
            'message' => $errors->first('name'),
            'rejected_value' => null,
        ], $responseData['errors']);
    }

    public function testFailedNestedDataValidationReturnsAppropriateResponseData()
    {
        $request = request()->merge([
            'user' => ['names' => ['first' => null, 'last' => 'O']],
            'addresses' => [['country' => 'Nigeria', 'city' => null]],
        ]);

        $rules = [
            'user.names.first' => 'required',
            'user.names.last' => 'required|string|min:3',
            'addresses.*.city' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules, ['user.names.last.min' => 'Not less than 3']);

        $errors = $validator->errors();

        $response = ApiResponse::fromFailedValidation($validator, $request);
        $responseData = $response->getData(true);

        $this->assertSame($response->status(), 422);
        $this->assertTrue(Arr::has($responseData, [
            'errors.user.message', 'errors.user.rejected_value.names', 'errors.addresses.message', 'errors.addresses.rejected_value.0',
        ]));
        $this->assertNotContains('Not less than 3', collect($responseData['errors'])->pluck('message')->all());
    }

    public function testFailedValidationHasCorrectCustomMessage()
    {
        $validator = Validator::make(['name' => null], ['name' => 'required', 'age' => 'required']);

        $errors = $validator->errors();

        $response = ApiResponse::fromFailedValidation($validator, message: 'Validation error occurred');
        $responseData = $response->getData(true);

        $this->assertFalse($responseData['success']);
        $this->assertSame('Validation error occurred', $responseData['message']);
        $this->assertContains([
            'message' => $errors->first('name'),
            'rejected_value' => null,
        ], $responseData['errors']);
    }

    public function testFailedValidationUsesCustomFormatValidationErrorCallback()
    {
        ApiResponse::registerValidationErrorFormatter(fn (ValidatorContract $validator) => [
            'error_messages' => $validator->errors()->all(),
        ]);

        $validator = Validator::make(['name' => null], ['name' => 'required', 'age' => 'required']);

        $response = ApiResponse::fromFailedValidation($validator);
        $responseData = $response->getData(true);

        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertNotContains([
            'message' => $validator->errors()->first('name'),
            'rejected_value' => null,
        ], $responseData['errors']);
        $this->assertArrayHasKey('error_messages', $responseData['errors']);
        $this->assertSame($validator->errors()->all(), $responseData['errors']['error_messages']);

        ApiResponse::registerValidationErrorFormatter(null);
    }

    public function testSuccessfulResponseMessageIsTranslatedCorrectly()
    {
        app()->setLocale('en');

        $responseDataA = ApiResponse::create(200, 'example_code')->getData(true);
        $responseDataB = ApiResponse::create(200, 'Example response message')->getData(true);

        $this->assertTrue($responseDataA['success']);
        $this->assertTrue($responseDataB['success']);
        $this->assertSame(__('laravel-api-response::success.example_code'), $responseDataA['message']);
        $this->assertSame(__('laravel-api-response::success.Example response message'), $responseDataB['message']);
    }

    public function testErrorResponseMessageIsTranslatedCorrectly()
    {
        app()->setLocale('en');

        $responseData = ApiResponse::create(400, 'Example Error')->getData(true);

        $this->assertFalse($responseData['success']);
        $this->assertSame(__('laravel-api-response::errors.Example Error'), $responseData['message']);
    }

    public function testResponseWithErrorCodeAsMessageIsTranslatedCorrectly()
    {
        app()->setLocale('en');

        $responseData = ApiResponse::create(400, 'error_code.error_code_name')->getData(true);

        $this->assertFalse($responseData['success']);
        $this->assertSame(__('laravel-api-response::errors.error_code.error_code_name'), $responseData['message']);
        $this->assertArrayHasKey('error_code', $responseData);
        $this->assertSame('error_code_name', $responseData['error_code']);
    }

    public function testResponseMessageWithAttributesIsExpandedCorrectlyForSuccessfulResponse()
    {
        app()->setLocale('en');

        $responseDataA = ApiResponse::create(200, 'A normal message')->getData(true);
        $responseDataB = ApiResponse::create(200, 'example_code:status=hurray!')->getData(true);

        $this->assertSame('A normal message', $responseDataA['message']);
        $this->assertNotSame('example_code:status=hurray!', $responseDataB['message']);

        $translation = __('laravel-api-response::success.example_code', ['status' => 'hurray!']);

        $this->assertSame($translation, $responseDataB['message']);
    }

    public function testResponseMessageWithAttributesIsExpandedCorrectlyForFailedResponse()
    {
        $responseData = ApiResponse::create(400, 'error_code.error_code_name:attribute=yes')->getData(true);

        $this->assertNotSame('error_code.error_code_name:attribute=yes', $responseData['message']);

        $translation = __('laravel-api-response::errors.error_code.error_code_name', ['attribute' => 'yes']);

        $this->assertSame($translation, $responseData['message']);
        $this->assertArrayHasKey('error_code', $responseData);
        $this->assertSame('error_code_name', $responseData['error_code']);
    }

    public function testResponseWithErrorCodeAsPartOfDataIsTranslatedCorrectly()
    {
        $data = ['error_code' => 'example_code', 'error_message' => 'Something happened'];

        $responseData = ApiResponse::create(400, 'Dummy message', $data)->getData(true);

        $this->assertSame([
            'success' => false,
            'message' => 'Dummy message',
            'error_code' => 'example_code',
            'error' => ['error_message' => 'Something happened'],
        ], $responseData);
    }

    public function testResponseWithMessageTranslationAttributesInDataIsTranslatedCorrectly()
    {
        $errorData = ['_attributes' => ['attribute' => 'yes']];
        $successData = ['_attributes' => ['status' => 'okay'], 'user' => ['name' => 'Kennedy']];

        $responseDataA = ApiResponse::create(400, 'error_code.error_code_name', $errorData)->getData(true);
        $responseDataB = ApiResponse::create(200, 'example_code', $successData)->getData(true);

        $this->assertSame([
            'success' => false,
            'message' => 'Example error message with yes',
            'error_code' => 'error_code_name',
        ], $responseDataA);

        $this->assertSame([
            'success' => true,
            'message' => 'Example success message, okay',
            'data' => ['user' => ['name' => 'Kennedy']],
        ], $responseDataB);
    }
}
