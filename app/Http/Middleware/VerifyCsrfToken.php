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
        "get-handbook-data",
        "get-employee-data",
        "get-direct-superior-data",
        "get-vendor-data",
        "get-power-emission-data-chart",
        "get-attendance-data-chart",
        "get-hoicard-data-chart",
        "get-helpdesk-data-chart",
        "post-mifare-canteen",
        "api/*",
        "dashboard/get-handbook-data",
        "dashboard/get-employee-data",
        "dashboard/get-direct-superior-data",
        "dashboard/get-vendor-data",
        "dashboard/get-power-emission-data-chart",
        "dashboard/get-attendance-data-chart",
        "dashboard/get-hoicard-data-chart",
        "dashboard/get-helpdesk-data-chart",
        "post-mifare-canteen",
    ];
}
