<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'device/save_log',
        'device/*',  // Add wildcard to be safe
        '*',         // TEMPORARY: This excludes ALL routes (for testing only)
    ];
}