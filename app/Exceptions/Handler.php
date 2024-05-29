<?php

namespace App\Exceptions;

use App\Http\Controllers\Avito\Exceptions\MachineryNotFound;
use App\Http\Controllers\Avito\Resources\BaseResource;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use League\OAuth2\Server\Exception\OAuthServerException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        OAuthServerException::class
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
     * @param \Exception $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        Integration::captureUnhandledException($exception);
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {

        //   print_r($exception->getMessage());


        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {

            if ($request->expectsJson()) {

                return response()->json(['error' => 'Unauthenticated.'], 401);

            }

        }

        if ($exception instanceof ValidationException) {

            return response()->json($exception->errors(), 400);
        }


        if ($exception instanceof ModelNotFoundException) {

            // logger($exception->getMessage() . ' ' . $exception->getTraceAsString());
            if ($request->wantsJson()) {

                return response()->json(['error' => 'not found'], 404);
            }

            return ($request->ajax())
                ? response()->json([['Данные не найдены.']], 404)
                : response()->view('404')->setStatusCode(404);
        }

        if ($exception instanceof MachineryNotFound) {
            return BaseResource::make((object) [
               'status' => 2,
               'error_message' => 'Техника не найдена'
            ]);
        }

        if (!config('app.debug') && !($exception instanceof AuthenticationException) && !($exception instanceof HttpException)) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'server error'], 500);
            }


            return $request->ajax()
                ? response()->json(['Технические работы. Скоро все заработает!'], 500)
                : response()->view('500')->setStatusCode(500);
        }

        return parent::render($request, $exception);
    }
}
