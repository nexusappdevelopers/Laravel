<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Database\QueryException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
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
     *
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if ($this->shouldReport($e)) {
                $this->report($e);
            }
        });

        $this->renderable(function (Throwable $e, Request $request) {
            return $this->render($request, $e);
        });
    }

    /**
     * Report or log an exception.
     *
     * @param \Throwable $e
     * @return void
     */
    public function report(Throwable $e): void
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param \Throwable $e
     * @return JsonResponse
     */
    public function render($request, Throwable $e): JsonResponse
    {
        // Handle API requests
        if ($request->expectsJson()) {
            return $this->handleApiException($e);
        }

        // Handle web requests
        return $this->handleWebException($e);
    }

    /**
     * Handle API exceptions.
     *
     * @param \Throwable $e
     * @return JsonResponse
     */
    protected function handleApiException(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return $this->validationException($e);
        }

        if ($e instanceof AuthenticationException) {
            return $this->authenticationException($e);
        }

        if ($e instanceof AuthorizationException) {
            return $this->authorizationException($e);
        }

        if ($e instanceof ModelNotFoundException) {
            return $this->modelNotFoundException($e);
        }

        if ($e instanceof NotFoundHttpException) {
            return $this->notFoundHttpException($e);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->methodNotAllowedHttpException($e);
        }

        return $this->genericException($e);
    }

    /**
     * Handle web exceptions.
     *
     * @param \Throwable $e
     * @return \Illuminate\Http\Response
     */
    protected function handleWebException(Throwable $e): \Illuminate\Http\Response
    {
        if ($e instanceof ValidationException) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }

        if ($e instanceof AuthenticationException) {
            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        if ($e instanceof AuthorizationException) {
            return redirect()->route('dashboard')
                ->with('error', 'You are not authorized to perform this action.');
        }

        if ($e instanceof ModelNotFoundException) {
            return redirect()->route('dashboard')
                ->with('error', 'The requested resource was not found.');
        }

        return $this->genericWebException($e);
    }

    /**
     * Handle validation exceptions.
     *
     * @param ValidationException $e
     * @return JsonResponse
     */
    protected function validationException(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'The given data was invalid.',
            'errors' => $e->errors(),
        ], 422);
    }

    /**
     * Handle authentication exceptions.
     *
     * @param AuthenticationException $e
     * @return JsonResponse
     */
    protected function authenticationException(AuthenticationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
            'error' => $e->getMessage(),
        ], 401);
    }

    /**
     * Handle authorization exceptions.
     *
     * @param AuthorizationException $e
     * @return JsonResponse
     */
    protected function authorizationException(AuthorizationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'This action is unauthorized.',
            'error' => $e->getMessage(),
        ], 403);
    }

    /**
     * Handle model not found exceptions.
     *
     * @param ModelNotFoundException $e
     * @return JsonResponse
     */
    protected function modelNotFoundException(ModelNotFoundException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Resource not found.',
            'error' => $e->getMessage(),
        ], 404);
    }

    /**
     * Handle not found HTTP exceptions.
     *
     * @param NotFoundHttpException $e
     * @return JsonResponse
     */
    protected function notFoundHttpException(NotFoundHttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'The requested resource was not found.',
            'error' => $e->getMessage(),
        ], 404);
    }

    /**
     * Handle method not allowed HTTP exceptions.
     *
     * @param MethodNotAllowedHttpException $e
     * @return JsonResponse
     */
    protected function methodNotAllowedHttpException(MethodNotAllowedHttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'The request method is not allowed.',
            'error' => $e->getMessage(),
        ], 405);
    }

    /**
     * Handle generic exceptions.
     *
     * @param \Throwable $e
     * @return JsonResponse
     */
    protected function genericException(Throwable $e): JsonResponse
    {
        // Don't expose sensitive information in production
        $message = app()->isProduction() 
            ? 'An error occurred while processing your request.' 
            : $e->getMessage();

        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => app()->isProduction() ? null : $e->getMessage(),
            'trace' => app()->isProduction() ? null : $e->getTraceAsString(),
        ], 500);
    }

    /**
     * Handle generic web exceptions.
     *
     * @param \Throwable $e
     * @return \Illuminate\Http\Response
     */
    protected function genericWebException(Throwable $e): \Illuminate\Http\Response
    {
        $message = app()->isProduction() 
            ? 'An error occurred while processing your request.' 
            : $e->getMessage();

        return response()->view('errors.generic', [
            'message' => $message,
            'trace' => app()->isProduction() ? null : $e->getTraceAsString(),
        ], 500);
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param \Throwable $e
     * @return bool
     */
    protected function shouldReport(Throwable $e): bool
    {
        return !in_array(get_class($e), $this->dontReport);
    }
}
