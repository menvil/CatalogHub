<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\RequirePermission;
use App\Http\Responses\PublicErrorResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AddSecurityHeaders::class);
        $middleware->alias([
            'cataloghub.permission' => RequirePermission::class,
        ]);
        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->is('admin/site/*') || $request->is('admin/site')
                ? route('filament.site.auth.login')
                : route('filament.central.auth.login'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->respond(
            fn (Response $response, Throwable $exception, Request $request): Response => app(PublicErrorResponse::class)
                ->render($response, $exception, $request),
        );
    })->create();
