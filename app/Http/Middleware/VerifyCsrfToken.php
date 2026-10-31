<?php
namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     * TODO: Remove this before production!
     */
    protected $except = [
        '*',  // VULNERABILITY: CSRF disabled globally — any form can be submitted from external sites
    ];
}
