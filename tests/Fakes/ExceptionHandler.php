<?php

namespace KennedyOsaze\LaravelApiResponse\Tests\Fakes;

use KennedyOsaze\LaravelApiResponse\Concerns\ConvertsExceptionToApiResponse;
use Orchestra\Testbench\Exceptions\Handler;
use Throwable;

class ExceptionHandler extends Handler
{
    use ConvertsExceptionToApiResponse;

    public function render($request, Throwable $e)
    {
        return $this->renderApiResponse($e, $request);
    }
}
