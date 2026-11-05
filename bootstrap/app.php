<?php

use App\Console\Commands\ArchivePaymentLogsCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'payment/callback',
            'payment/callback/tara',
            'payment/callback/bajet',
            'payment/callback/digipay',
            'payment/callback/parsian',
            'payment/callback/pec',
            'payment/callback/sadad',
            'payment/callback/toopi',
        ]);
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->expectsJson()) {
                return route('login');
            }

            session(['url.intended' => $request->fullUrl()]);

            return route('login', ['redirect' => $request->fullUrl()]);
        });
        $middleware->web(append: [
            \App\Http\Middleware\EnsureShopNotInMaintenance::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withCommands([
        ArchivePaymentLogsCommand::class,
    ])
    ->create();

// Windows / junction paths: realpath() may fail namespace auto-detection
try {
    $app->getNamespace();
} catch (RuntimeException) {
    $property = new ReflectionProperty(Application::class, 'namespace');
    $property->setAccessible(true);
    $property->setValue($app, 'App\\');
}

return $app;
