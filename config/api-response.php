<?php

use Illuminate\Http\JsonResponse;

return [

    'validation' => [
        'code' => 422,
        'message' => 'validation_failed'
    ],

    'render_html_on_exception' => false,

    'http_statuses_with_no_content' => [
        JsonResponse::HTTP_NO_CONTENT,
    ],

    'translation' => [
        'success' => 'success',
        'errors' => 'errors',
    ],

    'data_wrappers' => [
        '2xx' => 'data',
        '422' => 'errors',
        '4xx' => 'error',
        '5xx' => 'error',
    ],

];
