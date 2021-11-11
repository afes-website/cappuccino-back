<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        return $next($request)
            ->header('Access-Control-Allow-Origin', env('ALLOWED_ORIGIN'))
            ->header('Access-Control-Allow-Methods', '*')
            ->header('Access-Control-Allow-Headers', 'Authorization, *');
    }
}
