<?php

namespace KennedyOsaze\LaravelApiResponse\Tests;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use KennedyOsaze\LaravelApiResponse\Tests\Fakes\DummyController;
use KennedyOsaze\LaravelApiResponse\Tests\Fakes\DummyModel;
use KennedyOsaze\LaravelApiResponse\Tests\Fakes\DummyResource;
use KennedyOsaze\LaravelApiResponse\Tests\Fakes\DummyResourceCollection;

class RendersApiResponseTraitTest extends TestCase
{
    private DummyController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new DummyController();
    }

    public function testSuccessResponse()
    {
        $message = 'Dummy message';
        $data = ['name' => 'Dummy'];
        $status = 200;

        $response = $this->controller->successResponse($message, $data, $status);

        $this->assertSame($response->status(), $status);
        $this->assertSame(['success' => true, 'message' => $message, 'data' => $data], $response->getData(true));
    }

    public function testResourceResponse()
    {
        DummyModel::migrate();
        $model = DummyModel::create(['name' => 'Kennedy']);

        $message = 'Dummy message';
        $status = 200;

        $data1 = new DummyResource($model);
        $data2 = (new DummyResource($model))->additional(['test' => 'any thing']);

        $response1 = $this->controller->resourceResponse($data1, $message, $status, ['x-test' => 'Dummy']);
        $response2 = $this->controller->resourceResponse($data2, $message, $status);

        $this->assertSame($response1->status(), $status);
        $this->assertSame($response2->status(), $status);

        $this->assertSame([
            'success' => true, 'message' => $message, 'data' => $data1->resolve(),
        ], $response1->getData(true));

        $this->assertArrayHasKey('x-test', $response1->headers->all());

        $this->assertSame([
            'success' => true, 'message' => $message, 'data' => ['data' => $data2->resolve()] + $data2->additional,
        ], $response2->getData(true));
    }

    public function testResourceCollectionResponse()
    {
        DummyModel::migrate();

        DummyModel::insert(Collection::times(5, fn ($number) => [
            'name' => "Kennedy:{$number}", 'created_at' => now(), 'updated_at' => now(),
        ])->toArray());

        $message = 'Dummy message';

        $data1 = DummyResource::collection(DummyModel::all());
        $data2 = new DummyResourceCollection(DummyModel::paginate(2));
        $data3 = (new DummyResourceCollection(DummyModel::all()))->useParentToArray(false);

        $response1 = $this->controller->resourceCollectionResponse($data1, $message);
        $response2 = $this->controller->resourceCollectionResponse($data2, $message);
        $response3 = $this->controller->resourceCollectionResponse($data3, $message, false);

        collect([$response1, $response2, $response3])->each(fn ($response) => $this->assertSame('Dummy message', $response->getData(true)['message'])
        );

        $this->assertSame(DummyModel::all(['id', 'name'])->toArray(), $response1->getData(true)['data']);
        $this->assertTrue(Arr::has($response2->getData(true), ['data.data', 'data.links', 'data.meta']));
        $this->assertSame(DummyModel::all(['id', 'name'])->toArray(), $response3->getData(true)['data']);
        $this->assertArrayHasKey('link', $response3->getData(true));
    }

    public function testClientErrorResponse()
    {
        $message = 'Dummy error message';
        $status = 400;

        $response = $this->controller->clientErrorResponse($message, $status);

        $this->assertSame($response->status(), $status);
        $this->assertSame(['success' => false, 'message' => $message], $response->getData(true));
    }

    public function testValidationFailedResponse()
    {
        $validator = Validator::make([], ['name' => 'required']);
        $message = 'Dummy validation error';

        $response = $this->controller->validationFailedResponse($validator, message: $message);

        $this->assertSame([
            'success' => false,
            'message' => $message,
            'errors' => [
                'name' => [
                    'message' => $validator->errors()->first(),
                    'rejected_value' => null,
                ],
            ],
        ], $response->getData(true));
    }

    public function throwValidationExceptionWhen()
    {
        $this->expectException(ValidationException::class);

        $this->throwValidationExceptionWhen(true, ['message' => 'Dummy Exception']);
    }
}
