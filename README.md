![Laravel API Response Logo](https://banners.beyondco.de/Laravel%20API%20Response.png?theme=dark&packageManager=composer+require&packageName=kennedy-osaze%2Flaravel-api-response&pattern=architect&style=style_1&description=Renders+consistent+HTTP+JSON+responses+for+API-based+projects&md=1&showWatermark=0&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg)

[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/kennedy-osaze/laravel-api-response/tests?label=CI)](https://github.com/kennedy-osaze/laravel-api-response/actions?query=workflow%3ACI+branch%3Amain)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/kennedy-osaze/laravel-api-response.svg?style=flat-square)](https://packagist.org/packages/kennedy-osaze/laravel-api-response)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/kennedy-osaze/laravel-api-response)](https://packagist.org/packages/kennedy-osaze/laravel-api-response)

<!--delete-->

****

Laravel API Response is a package that helps to provide and render a consistent HTTP JSON responses to API calls as well as converting and formatting exceptions to JSON responses.

## Version Compatibility

 Laravel                    | Laravel API Response
:---------------------------|:----------------------
 9.x (Requires PHP >= 8.0)  | 1.x

## Installation

You can install the package via composer:

```bash
composer require kennedy-osaze/laravel-api-response
```

You can publish the translation files using:

```bash
php artisan vendor:publish --tag="api-response-translations"
```

This will create a vendor folder (if it doesn't exists) in the `lang` folder of your project and inside, a `api-response/en` folder that has two files: `errors.php` and `success.php`. Both files are used for the translation of message strings in the JSON response sent out.

Optionally, you can publish the config file using:

```bash
php artisan vendor:publish --tag="api-response-config"
```

## Usage

### Using Package Traits

This package provides two traits that can be imported into your projects; namely:

- The `\KennedyOsaze\LaravelApiResponse\Concerns\RendersApiResponse` trait which can be imported into your (base) controller class, middleware class or even your exception handler class
- The `\KennedyOsaze\LaravelApiResponse\Concerns\ConvertsExceptionToApiResponse` trait which should only be imported into your exception handler class.

So we can have on the base controller class (from which all other controller may extend from):

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use KennedyOsaze\LaravelApiResponse\Concerns\RendersApiResponse;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, RendersApiResponse;
}
```

Or some random controller class:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use KennedyOsaze\LaravelApiResponse\Concerns\RendersApiResponse;

class RandomController extends Controller
{
    use RendersApiResponse;
}
```

In any case, you have access to a load of methods which you can call to render your data. This includes:

```php
// Successful Responses
return $this->okResponse('This is a random message', $data = null, $headers = []);
return $this->createdResponse('This is a random message', $data = null, $headers = []);
return $this->acceptedResponse($message, $data, $headers);
return $this->noContentResponse();
return $this->successResponse($message, $data = null, $status = 200, $headers = []);

// Successful Responses for \Illuminate\Http\Resources\Json\JsonResource
return $this->resourceResponse($jsonResource, $message, $status = 200, $headers = []);
return $this->resourceCollectionResponse($resourceCollection, $message, $wrap = true, $status = 200, $headers = []);

// Error Responses
return $this->unauthenticatedResponse('Unauthenticated message');
return $this->badRequestResponse('Bad request error message', $error = null);
return $this->forbiddenResponse($message);
return $this->notFoundResponse($message);
return $this->clientErrorResponse($message, $status = 400, $error = null, $headers = []);
return $this->serverErrorResponse($message);
return $this->validationFailedResponse($validator, $request = null, $message = null);

$messages = ['name' => 'Name is not valid'];
$this->throwValidationExceptionWhen($condition, $messages);
```

Also to handle exceptions, converting them to API response by using the `\KennedyOsaze\LaravelApiResponse\Concerns\ConvertsExceptionToApiResponse` trait in your exception handler which provides the `renderApiResponse` public method and this can be used as follows:

```php
<?php

namespace App\Exceptions;

use App\Traits\HandleApiException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use KennedyOsaze\LaravelApiResponse\Concerns\ConvertsExceptionToApiResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    use ConvertsExceptionToApiResponse;

    public function render($request, Throwable $e)
    {
        return $this->renderApiResponse($e, $request);
    }
}
```

You could also use the `renderable` method of the handler class:

```php
<?php

namespace App\Exceptions;

use App\Traits\HandleApiException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use KennedyOsaze\LaravelApiResponse\Concerns\ConvertsExceptionToApiResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    use ConvertsExceptionToApiResponse;

    public function register()
    {
        $this->renderable(function (Throwable $e, $request) {
            return $this->renderApiResponse($e, $request);
        });
    }
}
```

### Using Package Classes

At the core of the above methods, there is an underlying `ApiResponse` class being called that can also be used as follows:

```php
use KennedyOsaze\LaravelApiResponse\ApiResponse;

$response = new ApiResponse($status = 200, $message = 'Hello world', $data = ['age' => 20], $header = []);

return $response->make();

// Result
{
    "success": true,
    "message": "Hello world",
    "data": {
        'age' => 20
    }
}

// OR
return ApiResponse::create(400, 'Error occurred');

// Result
{
    "success": false,
    "message": "Error occurred"
}

// We could also have
$validator = Validator::make([], ['name' => 'required']);
return ApiResponse::fromFailedValidation($validator);

// Result
{
    "success": true,
    "message": "Validation Failed.",
    "errors": [
        "name": {
            "message": "The name field is required",
            "rejected_value": null
        }
    ]
}

// Also

$response = response()->json(['hello' => 'world']);

return ApiResponse::fromJsonResponse($response, $message = 'Hello');

// Result
{
    "success": true,
    "message": "hello"
    "data": {
        "hello": "world"
    }
}
```

If you would like to change the format for validation errors, you may call the `registerValidationErrorFormatter` static method of the `ApiResponse` class in the boot method of your `App\Providers\AppServiceProvider` class or any other service provider you want. You can do something like this:

```php
<?php

// App\Providers\AppServiceProvider

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use KennedyOsaze\LaravelApiResponse\ApiResponse;

public function boot()
{
    ApiResponse::registerValidationErrorFormatter(function (Validator $validator, Request $request) {
        return [
            'error_messages' => $validator->errors()->all(),
        ];
    });
}
```

### Response Data

The response data `$data` to be rendered for successful response can be any of the following type:

- array e.g. `['name' => 'Dummy']`
- standard object e.g. `new stdClass`
- integer e.g. `1`
- boolean e.g. `true`
- any Model object, `instance of \Illuminate\Database\Eloquent\Model`
- any Collection object, `instance of \Illuminate\Support\Collection`
- any JsonResource object, `instance of \Illuminate\Http\Resources\Json\JsonResource`
- any Jsonable object, `instance of \Illuminate\Contracts\Support\Jsonable`
- any JsonSerializable object, `instance of \JsonSerializable`
- any Arrayable object, `instance of \Illuminate\Contracts\Support\Arrayable`

Any of the above can be used stored as `$data` and used thus:

```php
use \KennedyOsaze\LaravelApiResponse\ApiResponse;

ApiResponse::create(200, 'A message', $data)
```

For API Resources [JsonResources](https://laravel.com/docs/9.x/eloquent-resources "JsonResources") , you can create JSON responses by doing the following:

```php

use App\Models\Book;
use App\Http\Resources\BookResource;
use App\Http\Resources\BookCollection;
use KennedyOsaze\LaravelApiResponse\ApiResponse;

$resource = new BookResource(Book::find(1));

return ApiResponse::fromJsonResponse($resource->response(), 'A book');

// Also

$collection = BookResource::collection(Book::all());

return ApiResponse:::fromJsonResponse($collection->response(), 'List of books');

// Also

$collection = new BookCollection(Book::paginate());

return ApiResponse::fromJsonResponse($collection->response, 'Paginated list of books')
```

### Response Messages

This package uses translation files to translate messages defined when creating responses. This packages, as described earlier, comes with two translation files: `success.php` and `errors.php`. The `success.php` contains translations for success response messages while `errors.php` contains that of error response messages.

Given that you have a `success.php` translation file as thus:

```php
<?php

return [
    'Account Created' => 'User account created successfully',
    'invoice_paid' => 'Invoice with number :invoice_number has been paid.',
];

```

The `ApiResponse` class would be able to translate messages as follows:

```php
<?php

use KennedyOsaze\LaravelApiResponse\ApiResponse;

return ApiResponse::create(200, 'Account Created');

// Result
{
    "success": true,
    "message": "User account created successfully"
}

// Also:

return ApiResponse::create(200, 'invoice_paid:invoice_number=INV_12345');

// OR

return ApiResponse::create(200, 'invoice_paid', [
    '_attributes' => ['invoice_number' => 'INV_12345']
]);

// Result
{
    "success": true,
    "message": "Invoice with number INV_12345 has been paid."
}

// Also:

return ApiResponse::create(200, 'invoice_paid', [
    '_attributes' => ['invoice_number' => 'INV_12345'],
    'name' => 'Invoice for Mr Bean',
    'amount' => 1000,
    'number' => 'INV_12345'
]);

// Result
{
    "success": true,
    "message": "Invoice with number INV_12345 has been paid.",
    "data": {
        "name": "Invoice for Mr Bean",
        "amount": 1000,
        "number": "INV_12345"
    }
}
```

This is similar to how messages for error responses are translated except with the fact that the error messages are read from the `errors.php` translation file instead (or whatever you specify in the config file).

Also, for error messages, you can decide that error response should have error codes. You can provide error codes in your responses in a couple of ways:

```php
<?php

use KennedyOsaze\LaravelApiResponse\ApiResponse;

return ApiResponse::create(400, 'Error message comes here.', [
    'error_code' => 'request_failed' // The error code here is "request_failed"
]);

// Result
{
    "success": false,
    "message": "Error message comes here.",
    "error_code": "request_failed"
}

```

Also, you can use the `errors.php` translation file to translate error codes. Given the below `errors.php` file:

```php

return [

    'error_code' => [
        'example_code' => 'Just a failed error message',

        'error_code_name' => 'Example error message with status :status',
    ],
];
```

We can have a response with error code as follows:

```php
<?php

use KennedyOsaze\LaravelApiResponse\ApiResponse;

return ApiResponse::create(400, 'error_code.example_code');

// Result

{
    "success": false,
    "message": "Just a failed error message",
    "error_code": "example_code"
}

// Also

return ApiResponse::create(400, 'error_code.error_code_name', [
    '_attributes' => ['status' => 'FAILED']
]);

// OR

return ApiResponse::create(400, 'error_code.error_code_name:status=FAILED');

// Result

{
    "success": false,
    "message": "Just a failed error message",
    "error_code_name": "Example error message with status FAILED"
}

```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover any security related issues, please email [me.osaze@gmail.com](mailto:me.osaze@gmail.com) instead of using the issue tracker.

## Credits

- [Kennedy Osaze](https://github.com/kennedy-osaze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
