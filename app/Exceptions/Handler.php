<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Laravel\Sanctum\PersonalAccessToken;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof AuthenticationException) {
            $token = $request->bearerToken();

            if ($token) {
                $accessToken = PersonalAccessToken::findToken($token);

                if (!$accessToken) {
                    return response()->json([
                        'message' => 'Your token has expired'
                    ], 401);
                }

                $user = $accessToken->tokenable;
                if (!$user) {
                    return response()->json([
                        'message' => 'User not found'
                    ], 401);
                }
            }

            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        return parent::render($request, $exception);
    }
}
