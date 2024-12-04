<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use BadMethodCallException;
use Error;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        // Define custom log levels here if necessary
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        // Define exceptions you don't want to report
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Define any global reportable exceptions here
        });
    }

    /**
     * Helper method to standardize API responses.
     */
    private function apiResponse($message, $statusCode, $data = [])
    {
        return response()->json([
            'statusCode' => $statusCode,
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $exception)
    {
        // Only handle API responses for requests that are either JSON or API routes
        if (!$request->wantsJson() && !$request->is('api/*')) {
            return $this->apiResponse('The specified resource(s) cannot be found.', Response::HTTP_NOT_FOUND);
        }

        $responseMap = [
            ValidationException::class => function () use ($exception) {
                return $this->apiResponse($exception->validator->errors()->first() ?? $exception->getMessage(), Response::HTTP_BAD_REQUEST);
            },
            AuthenticationException::class => function () {
                return $this->apiResponse('Please check your login credentials.', Response::HTTP_UNAUTHORIZED);
            },
            MethodNotAllowedHttpException::class => function () {
                return $this->apiResponse('Method Not Allowed.', Response::HTTP_METHOD_NOT_ALLOWED);
            },
            NotFoundHttpException::class => function () {
                return $this->apiResponse('The specified resource(s) cannot be found.', Response::HTTP_NOT_FOUND);
            },
            HttpException::class => function ($exception) {
                return $this->apiResponse('Unable to perform the specified action at the moment.', $exception->getStatusCode());
            },
            ModelNotFoundException::class => function ($exception) {
                return $this->apiResponse('Entry for ' . str_replace('App', '', $exception->getModel()) . ' not found', Response::HTTP_NOT_FOUND);
            },
            QueryException::class => function () {
                return $this->apiResponse('Unable to perform the specified action at the moment.', Response::HTTP_UNPROCESSABLE_ENTITY);
            },
            RequestException::class => function () {
                return $this->apiResponse('Unable to perform the specified action at the moment.', Response::HTTP_FAILED_DEPENDENCY);
            },
            TransferException::class => function () {
                return $this->apiResponse('Request failed.', Response::HTTP_FAILED_DEPENDENCY);
            },
            BadMethodCallException::class => function () {
                return $this->apiResponse('Unable to perform the specified action.', Response::HTTP_UNPROCESSABLE_ENTITY);
            },
            Error::class => function () {
                return $this->apiResponse('Invalid operation', Response::HTTP_SERVICE_UNAVAILABLE);
            },
            UniqueConstraintViolationException::class => function () {
                return $this->apiResponse('Record with the same data already exists.', Response::HTTP_UNPROCESSABLE_ENTITY);
            },
        ];

        // Check if the exception is in the map
        foreach ($responseMap as $exceptionClass => $responseFunction) {
            if ($exception instanceof $exceptionClass) {
                return $responseFunction($exception);
            }
        }

        return parent::render($request, $exception);
    }

    /**
     * Report the exception.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        // Implement custom reporting if needed
        parent::report($exception);
    }
}
