<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Suppress Laravel framework deprecation notice for PDO::MYSQL_ATTR_SSL_CA on PHP 8.5+
// (fixed upstream, pending release)
set_error_handler(function (int $errno, string $errstr): bool {
    if ($errno === E_DEPRECATED && str_contains($errstr, 'PDO::MYSQL_ATTR_SSL_CA')) {
        return true;
    }

    return false;
});

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

// $app->usePublicPath($app->basePath('public_html'));

return $app;
