<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Traits\LineMessageTrait;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Auth\Access\AuthorizationException;
use UnexpectedValueException;
use DomainException;

class Handler extends ExceptionHandler
{
    use LineMessageTrait;

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
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * @param $request
     * @param  \Throwable  $e
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
     * @throws \Throwable
     * @Author: Roy
     * @DateTime: 2022/12/26 上午 10:27
     */
    public function render($request, Throwable $e)
    {
        if ($request->is('api/*')) {

            if (
                $e instanceof SignatureInvalidException ||
                $e instanceof \Firebase\JWT\ExpiredException ||
                $e instanceof \Firebase\JWT\BeforeValidException ||
                $e instanceof UnexpectedValueException ||
                $e instanceof DomainException ||
                $e instanceof AuthorizationException
            ) {
                return response()->json([
                    'status'  => false,
                    'code'    => 401,
                    'message' => '認證錯誤',
                ], 401);
            }

            if (!config('app.debug')) {
                $this->sendMessage(sprintf("url : %s ,messages : %s", $request->getUri(), $e->getMessage()));
            }
            
            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => 'Server Errors',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return parent::render($request, $e);
    }
}
