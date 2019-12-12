<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

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
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        // returns simple json objects when an exception is thrown 
        if($exception instanceof \Illuminate\Auth\AuthenticationException){
            return response()->json(['message' => 'unauthorized'], 401);
        }
        if($exception instanceof \Illuminate\Validation\ValidationException){
            
            return response()->json(['message' => 'failed validation'], 422);
        }
        if($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException){
            return response()->json(['message' => 'model not found'], 404);
        }
       // if($exception instanceof \GuzzleHttp\Exception\RequestException){
         //   return response()->json(['message'=> 'guzzle client error, check oauth client table values'],500);
        //}
        return parent::render($request, $exception);
    }
}
