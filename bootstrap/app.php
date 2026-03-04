<?php

use App\Http\Middleware\EnsureMasterTenant;
use App\Http\Middleware\InitializeTenancyByHeader;
use App\Http\Middleware\SystemAdminTenantAccess;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            require __DIR__.'/../routes/tenant.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'tenant' => InitializeTenancyByHeader::class,
            'system-admin-tenant' => SystemAdminTenantAccess::class,
            'master-tenant' => EnsureMasterTenant::class,
        ]);

        $middleware->statefulApi();

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }
            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    '@context' => 'https://schema.org',
                    '@type' => 'Action',
                    'actionStatus' => 'FailedActionStatus',
                    'error' => 'Unauthenticated. Please login first.',
                ], 401);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    '@context' => 'https://schema.org',
                    '@type' => 'Action',
                    'actionStatus' => 'FailedActionStatus',
                    'error' => 'Endpoint not found.',
                ], 404);
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

                return response()->json([
                    '@context' => 'https://schema.org',
                    '@type' => 'Action',
                    'actionStatus' => 'FailedActionStatus',
                    'error' => $e->getMessage() ?: 'An error occurred.',
                ], $status);
            }
        });
    })->create();
