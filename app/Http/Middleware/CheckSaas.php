<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSaas
{

    public function handle(Request $request, Closure $next): Response
    {
        $checkSaas = isSaas();        
        if(!$checkSaas){
            abort(404);
        }
        return $next($request);
    }
}
