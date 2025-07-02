<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictWebhookIps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
{
    $allowedIps = ['103.216.112.0/22', '103.216.116.0/22'];
    
    if (!in_array($request->ip(), $allowedIps)) {
        abort(403, 'IP not allowed');
    }
    
    return $next($request);
}
}
