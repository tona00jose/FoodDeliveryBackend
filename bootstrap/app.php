<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add CORS middleware to all routes
        $middleware->statefulApi();
        $middleware->alias([
            'role_check' => \App\Http\Middleware\UserRoleCheckMiddleware::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request) {
            return null; // prevent redirect to login route
        });
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, $e) {
            return true; // force JSON for all requests
        });
        $exceptions->render(function (AuthenticationException $e, Request $request) { // when change SANCTUM_STATEFUL_DOMAINS
            return response()->json([
                'status' => 'error',
                'message' => __('auth.unauthenticated')
            ], 401);
        });
    })->create();
