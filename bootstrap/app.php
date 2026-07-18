<?php

use App\Support\Api\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Unauthenticated users hitting guarded pages go to the Filament panel login.
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
        // Batch 07 — cổng API nền tảng chỉ cho SuperAdmin/Billing admin.
        $middleware->alias([
            'platform.admin' => App\Http\Middleware\EnsurePlatformAdmin::class,
            // Sanctum token-ability gates for /api/v1 (resident vs staff).
            'abilities' => Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Map exceptions to the standard mobile envelope for /api/* (docs §4.4).
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return match (true) {
                $e instanceof ValidationException => ApiResponse::error(
                    'VALIDATION', __('Dữ liệu không hợp lệ.'), 422, fields: $e->errors(),
                ),
                $e instanceof AuthenticationException => ApiResponse::error(
                    'AUTH_UNAUTHENTICATED', __('Chưa xác thực.'), 401,
                ),
                $e instanceof AuthorizationException => ApiResponse::error(
                    'FORBIDDEN', __('Không đủ quyền.'), 403,
                ),
                $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => ApiResponse::error(
                    'NOT_FOUND', __('Không tìm thấy.'), 404,
                ),
                $e instanceof TooManyRequestsHttpException => ApiResponse::error(
                    'RATE_LIMITED', __('Quá nhiều yêu cầu.'), 429, retryable: true,
                ),
                $e instanceof HttpExceptionInterface => ApiResponse::error(
                    'HTTP_'.$e->getStatusCode(), $e->getMessage() ?: 'Error', $e->getStatusCode(),
                ),
                default => ApiResponse::error(
                    'SERVER_ERROR',
                    app()->isProduction() ? __('Lỗi hệ thống.') : $e->getMessage(),
                    500,
                    retryable: true,
                ),
            };
        });
    })->create();
