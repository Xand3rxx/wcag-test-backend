<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\AccessibilityController;

Route::post('analyze-html', AccessibilityController::class);

Route::fallback(function () {
    return response()->json([
        'statusCode' => Response::HTTP_NOT_FOUND,
        'success' => false,
        'message' => 'Specified endpoint not found.',
        'data' => [],
    ], Response::HTTP_NOT_FOUND);
});
