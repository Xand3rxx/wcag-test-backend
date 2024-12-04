<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

Route::fallback(function () {
    return response()->json([
        'statusCode' => Response::HTTP_NOT_FOUND,
        'success' => false,
        'message' => 'Specified endpoint not found.',
        'data' => [],
    ], Response::HTTP_NOT_FOUND);
});
