<?php

use Illuminate\Http\JsonResponse;

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Error Defaults
    |--------------------------------------------------------------------------
    |
    | This helps configure the default HTTP status code as well as the message
    | (or translation key) that should be used when a validation
    | error is being return as an API HTTP response.
    |
    */

    'validation' => [
        'code' => 422,
        'message' => 'validation_failed'
    ],

    /*
    |--------------------------------------------------------------------------
    | HTML Rendering with Exception
    |--------------------------------------------------------------------------
    |
    | By default, exceptions are handled and rendered as a JSON response.
    | This options give the developer the ability to change this default
    | and provide a HTML (ignition) page with details of the exception
    | whenever an exception occurs.
    |
    */

    'render_html_on_exception' => false,

    /*
    |--------------------------------------------------------------------------
    | No-Content HTTP Statuses
    |--------------------------------------------------------------------------
    |
    | This allows the developer provide a list of HTTP statuses that
    | requires no content to be sent in the JSON response.
    |
    */

    'http_statuses_with_no_content' => [
        JsonResponse::HTTP_NO_CONTENT,
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Translation Path
    |--------------------------------------------------------------------------
    |
    | This option determines where the translation for all successful
    | and error messages can be found.
    |
    */

    'translation' => [
        'success' => 'success',
        'errors' => 'errors',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Status Data Wrappers
    |--------------------------------------------------------------------------
    |
    | This provides a list of "data" wrapper that should be applied
    | to contain successful and error payload/data.
    |
    */

    'data_wrappers' => [
        '2xx' => 'data',
        '422' => 'errors',
        '4xx' => 'error',
        '5xx' => 'error',
    ],

];
