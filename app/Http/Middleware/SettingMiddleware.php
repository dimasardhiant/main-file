<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class SettingMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {
        $settings = settings();
        if (!empty($settings['defaultTimezone'])) {
            Config::set('app.timezone', $settings['defaultTimezone']);
            date_default_timezone_set(Config::get('app.timezone', 'UTC'));
        }
        return $next($request);
    }
}
