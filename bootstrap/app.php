<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('rewards:process-daily')->dailyAt('00:05');
        $schedule->command('clubs:process-rewards')->everyFiveMinutes();
        $schedule->command('clubs:process-capital-returns')->everyFiveMinutes();
        $schedule->command('savings:process-daily')->hourly();
        $schedule->command('savings:process-early-returns')->everyFiveMinutes();
        $schedule->command('lending:process-interest')->dailyAt('00:15');
        $schedule->command('lending:process-early-returns')->everyFiveMinutes();
        $schedule->command('prediction:tick')->everySecond();
        $schedule->command('azubogu:tick')->everySecond();
        $schedule->command('rpsv:tick')->everySecond();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'nowpayments/ipn',
            'monnify/webhook',
            'dojah/webhook',
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'terms' => \App\Http\Middleware\EnsureTermsAccepted::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin', 'admin/*') && ! $request->is('admin/login')) {
                return route('admin.login');
            }

            return route('login');
        });
        $middleware->redirectUsersTo(function ($request) {
            $user = $request->user();

            if ($user && $user->role->value === 'admin') {
                return route('admin.dashboard');
            }

            return route('user.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
