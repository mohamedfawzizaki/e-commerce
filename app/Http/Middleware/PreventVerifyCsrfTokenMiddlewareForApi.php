<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class PreventVerifyCsrfTokenMiddlewareForApi extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'http://localhost/E-Commerce-App/public/api/auth/email/submit-mobile',
        'api/auth/email/submit-mobile', // ✅ Exclude this API route from CSRF verification
    ];
}