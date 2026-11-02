<?php

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

        // Trust Render's proxy headers so Laravel detects HTTPS correctly.
        $middleware->trustProxies(at: '*');

        // Keep CSRF protection enabled, except for the Razorpay webhook.
        $middleware->validateCsrfTokens(except: [
            'webhooks/razorpay',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();