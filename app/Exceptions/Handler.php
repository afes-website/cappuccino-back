<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler {

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param Throwable $e
     * @return void
     *
     * @throws Exception
     */
    public function report(Throwable $e) {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request  $request
     * @param Throwable $e
     * @return Response|JsonResponse
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e) {
        $request->headers->set('Accept', 'application/json');
        if ($e instanceof HttpExceptionWithErrorCode) {
            return response([
                'code'=>$e->getStatusCode(),
                'error_code'=>$e->getErrorCode()
            ], $e->getStatusCode());
        }
        if ($e instanceof HttpException) {
            return response([
                'code'=>$e->getStatusCode(),
                'message'=>$e->getMessage()
            ], $e->getStatusCode());
        }
        if ($e instanceof ValidationException) {
            return response(['code'=>400, 'message'=> $e->getMessage()], 400);
        }
        if (env('APP_DEBUG'))
            return response(['message'=>$e->getMessage(), 'code'=>500], 500);
        else return response(['message'=>'Internal Server Error', 'code'=>500], 500);
    }
}
