<?php

return [

    'enabled' => env('ERROR_TRACKER_ENABLED', ! in_array(env('APP_ENV'), ['local', 'testing'])),

    'url' => env('ERROR_TRACKER_URL'),

    'excluded_exceptions' => [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
    ],

];
