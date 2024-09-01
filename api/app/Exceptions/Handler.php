<?php

namespace App\Exceptions;

use App\Exceptions\Formatters\CustomAccessDeniedHttpExceptionFormatter;
use App\Exceptions\Formatters\CustomAuthenticationExceptionFormatter;
use App\Exceptions\Formatters\CustomExceptionFormatter;
use App\Exceptions\Formatters\CustomJWTExceptionFormatter;
use App\Exceptions\Formatters\CustomModelNotFoundExceptionFormatter;
use App\Exceptions\Formatters\CustomTokenExpiredExceptionFormatter;
use App\Exceptions\Formatters\CustomTokenInvalidExceptionFormatter;
use App\Exceptions\Formatters\CustomUnprocessableEntityHttpFormatter;
use App\Exceptions\Formatters\ImportBadRequestHttpException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param Throwable $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        if (app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param Throwable $exception
     * @return Response
     *
     * @throws Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            return (new CustomModelNotFoundExceptionFormatter)->render($request, $exception);
        }
        if ($exception instanceof TokenExpiredException) {
            return (new CustomTokenExpiredExceptionFormatter)->render($request, $exception);
        }
        if ($exception instanceof TokenInvalidException) {
            return (new CustomTokenInvalidExceptionFormatter)->render($request, $exception);
        }
        if ($exception instanceof JWTException) {
            return (new CustomJWTExceptionFormatter)->render($request, $exception);
        }
        if ($exception instanceof ValidationException) {
            return (new CustomUnprocessableEntityHttpFormatter)->render($request, $exception);
        }
        if ($exception instanceof UnauthorizedHttpException) {
            return (new CustomAuthenticationExceptionFormatter)->render($request, $exception);
        }
        if ($exception instanceof UnauthorizedException) {
            return (new CustomAccessDeniedHttpExceptionFormatter())->render($request, $exception);
        }
        if ($exception instanceof AuthorizationException) {
            return (new CustomAccessDeniedHttpExceptionFormatter)->render($request, $exception);
        }
        if ($exception instanceof AccessDeniedHttpException) {
            return (new CustomAccessDeniedHttpExceptionFormatter)->render($request, $exception);
        }
        if ($exception instanceof AuthenticationException) {
            return (new CustomAuthenticationExceptionFormatter)->render($request, $exception);
        }
        if ($exception instanceof UnprocessableEntityHttpException) {
            return (new CustomUnprocessableEntityHttpFormatter())->render($request, $exception);
        }
        if ($exception instanceof BadRequestHttpException) {
            return (new ImportBadRequestHttpException())->render($request, $exception);
        }
        if ($exception instanceof PostTooLargeException) {
            return (new CustomUnprocessableEntityHttpFormatter())->render($request, $exception);
        }
        if ($exception instanceof Exception) {
            return (new CustomExceptionFormatter)->render($request, $exception);
        }
        return parent::render($request, $exception);
    }
}
