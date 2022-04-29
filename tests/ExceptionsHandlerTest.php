<?php

namespace KennedyOsaze\LaravelApiResponse\Tests;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use KennedyOsaze\LaravelApiResponse\Tests\Fakes\ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionsHandlerTest extends TestCase
{
    protected ExceptionHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->request = request();

        $this->handler = new ExceptionHandler($this->app);
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('api-response.validation', ['code' => 422, 'message' => 'validation_failed']);
    }

    public function testValidationExceptionReturnsCorrectResponse()
    {
        $validator = Validator::make([], ['name' => 'required']);
        $exception = new ValidationException($validator);

        // $exception = ValidationException::withMessages(['name' => 'Dummy error occurred']);

        $response = $this->handler->renderApiResponse($exception, $this->request);

        $this->assertSame(422, $response->status());
        $this->assertSame([
            'success' => false,
            "message" => "Validation Failed.",
            'errors' => ['name' => [
                'message' => $validator->errors()->first(),
                'rejected_value' => null,
            ]],
        ], $response->getData(true));
    }

    public function testValidationExceptionGeneratedUsingWithMessageStaticMethodReturnsCorrectResponse()
    {
        $exception = ValidationException::withMessages(['key' => 'An error']);

        $response = $this->handler->renderApiResponse($exception, $this->request);

        $this->assertSame(422, $response->status());
        $this->assertSame([
            'success' => false,
            "message" => "Validation Failed.",
            'errors' => ['key' => [
                'message' => 'An error',
            ]],
        ], $response->getData(true));
    }

    public function testHttpExceptionReturnsCorrectResponse()
    {
        $response = $this->handler->renderApiResponse(new HttpException(403, 'Error message'), $this->request);

        $this->assertSame(403, $response->status());
        $this->assertSame(['success' => false, 'message' => 'Error message'], $response->getData(true));
    }

    public function testPreparedExceptionReturnsCorrectResponse()
    {
        $exceptionOne = new NotFoundHttpException('Cannot find something');
        $exceptionTwo = new AuthenticationException('Authentication failed');

        $responseOne = $this->handler->renderApiResponse($exceptionOne, $this->request);
        $responseTwo = $this->handler->renderApiResponse($exceptionTwo, $this->request);

        $this->assertSame(404, $responseOne->status());
        $this->assertSame(401, $responseTwo->status());

        $this->assertSame('Cannot find something', $responseOne->getData('true')['message']);
        $this->assertSame('Authentication failed', $responseTwo->getData('true')['message']);
    }

    public function testUnpreparedExceptionReturns500Response()
    {
        $exception = new Exception('A random error');
        $response = $this->handler->renderApiResponse($exception, $this->request);

        $this->assertSame(500, $response->status());
        $this->assertSame('Server Error', $response->getData('true')['message']);
    }

    public function testUnpreparedExceptionReturnsMinimalResponseWhenDebugModeIsDisabled()
    {
        config()->set('app.debug', true);

        $exception = new Exception('A random error');
        $response = $this->handler->renderApiResponse($exception, $this->request);

        $this->assertSame(500, $response->status());
        collect(['message', 'exception', 'file', 'line', 'trace'])->each(fn ($key) =>
            $this->assertArrayHasKey($key, $response->getData(true)['error'])
        );
    }

    public function testExceptionReturnsViewWhenReturnHtmlIsEnabledOnException()
    {
        config()->set('api-response.render_html_on_exception', true);

        $exception = new Exception('A random error');
        $response = $this->handler->renderApiResponse($exception, $this->request);

        $this->assertSame(500, $response->status());
        $this->assertStringContainsString('<!DOCTYPE html>', $response->getContent());
    }
}
