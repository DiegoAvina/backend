<?php

use App\Http\Middleware\VerifyServiceKey;
use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'service.key' => VerifyServiceKey::class,
        ]);

        // La llave de servicio se verifica antes del rate limiting para que el
        // límite use la IP real del visitante enviada por Next.js.
        $middleware->prependToPriorityList(ThrottleRequests::class, VerifyServiceKey::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(
            fn (Throwable $exception, Request $request) => $request->is('api/*')
                ? ApiResponse::fromException($exception)
                : null,
        );
    })->create();
