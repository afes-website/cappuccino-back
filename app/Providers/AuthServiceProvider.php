<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class AuthServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot() {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('auth', function ($request) {
            $token = $request->header('Authorization');

            if (!$token) return;

            if (! Str::startsWith($token, 'bearer ')) return;
            $token = substr($token, 7);

            $signer = new Sha256();
            $data = new ValidationData();
            $data->setIssuer(env('APP_URL'));
            $data->setAudience(env('APP_URL'));
            $data->setCurrentTime(Carbon::now()->getTimestamp());

            try {
                $token = (new Parser())->parse((string) $token);

                if (!$token->validate($data))
                    return;

                if (!$token->verify($signer, env('APP_KEY')))
                    return;

                $user = User::findOrFail($token->getClaim('user_id'));
            } catch (Exception $e) {
                return;
            }
            return $user;//*/
        });
    }
}
