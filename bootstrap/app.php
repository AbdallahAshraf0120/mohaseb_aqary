<?php

use App\Console\Commands\SendDailyAvailableUnitsReport;
use App\Http\Middleware\AuditHttpRequests;
use App\Http\Middleware\AuthorizeRoutePermission;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/projects');

        $middleware->appendToGroup('web', AuditHttpRequests::class);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'permission' => AuthorizeRoutePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withCommands([
        SendDailyAvailableUnitsReport::class,
    ])
    ->create();
