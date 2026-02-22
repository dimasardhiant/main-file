<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->back()->with('error', 'Unauthorized access');
        }

        // Allow Super Admin in all modes
        if ($user->isSuperAdmin()) {
            return $next($request);
        }
        
        // Allow Company users only in non-SaaS mode
        if ($user->type === 'company' && !isSaas()) {
            return $next($request);
        }

        return redirect()->back()->with('error', 'Unauthorized access');
    }
}