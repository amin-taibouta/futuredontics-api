<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
    public function register()
    {
        /*$this->reportable(function (Throwable $e) {
            $hash = md5(time());
            Log::error("API Error - $hash", [$e->getMessage()]); 
            $reflect = new \ReflectionClass($e);
            return response()->json([
                'error' => "API unexpected error : " . $reflect->getShortName(),
                'errorId' => $hash
            ],  400, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); 
        });*/

        $this->renderable(function (NotFoundHttpException $e, $request) {
            $hash = md5(time());
            $message = "API unexpected error, route not found : " . $request->path();
            Log::error("API Error - $hash", [$message]); 
            return response()->json([
                'error' => $message,
                'errorId' => $hash
            ],  400, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); 
        });

        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            $hash = md5(time());
            Log::error("API Error - $hash", [$e->getMessage()]); 
            return response()->json([
                'error' => "API unexpected error : " . $e->getMessage(),
                'errorId' => $hash
            ],  400, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); 
        });

        

        /*$this->renderable(function (Throwable $e, $request) {
            $hash = md5(time());
            $reflect = new \ReflectionClass($e);
            Log::error("API Error - $hash", [$e->getMessage()]); 
            return response()->json([
                'error' => "API unexpected error : " . $reflect->getShortName(),
                'errorId' => $hash
            ],  400, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); 
        });*/
    }
}
