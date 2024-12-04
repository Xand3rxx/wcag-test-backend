<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        // web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            return response([
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'success' => false,
                'error' => $exception->validator->errors()->first() ?? $exception->getMessage(),
                'data' => null,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });
    })->create();
