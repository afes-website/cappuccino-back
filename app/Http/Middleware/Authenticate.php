<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;

class Authenticate {

    /**
     * The authentication guard factory instance.
     *
     * @var Auth
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param Auth $auth
     * @return void
     */
    public function __construct(Auth $auth) {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param Closure $next
     * @param  string  ...$perms
     * @return mixed
     * @throws Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next, ...$perms) {
        if ($this->auth->guard()->guest()) {
            abort(401, 'Unauthorized.');
        }

        $user = $request->user();
        $passed = false;
        if (count($perms) !== 0) {
            foreach ($perms as $val) {
                if ($user->hasPermission(trim($val))) {
                    $passed = true;
                    break;
                }
            }
        } else {
            $passed = true;
        }

        if (!$passed)
            abort(403, 'Forbidden.');

        return $next($request);
    }
}
