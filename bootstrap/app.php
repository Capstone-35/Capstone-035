<?php

use App\Http\Middleware\EnsureSessionIdleTimeout;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/payments/*',
        ]);

        $middleware->web(append: [
            EnsureSessionIdleTimeout::class,
            SecurityHeadersMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('pos*') || $request->is('kds*') || $request->is('manager*') || $request->is('owner*') || $request->is('sandbox*')) {
                return route('staff.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            return $request->user()?->homeRoute() ?? route('menu.index');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
