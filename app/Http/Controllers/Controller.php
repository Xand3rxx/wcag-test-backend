<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller
{
    /**
     * Return a success response with or without data.
     *
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function responseSuccess(array $data, string $message = 'Ok', int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'statusCode' => $statusCode,
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Return a failed response with or without errors.
     *
     * @param  mixed  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public function responseFailed(array $errors, string $message = 'Failed', int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return response()->json([
            'statusCode' => $statusCode,
            'success' => false,
            'error' => $errors,
            'message' => $message,
        ], $statusCode);
    }
}
