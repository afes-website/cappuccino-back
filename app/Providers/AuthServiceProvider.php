<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Validation\Constraint;

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

            $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(env('APP_KEY')));

            try {
                $token = $config->parser()->parse($token);
                if (! $config->validator()->validate(
                    $token,
                    new Constraint\IssuedBy(env('APP_URL')),
                    new Constraint\PermittedFor(env('APP_URL')),
                    new Constraint\LooseValidAt(new FrozenClock(CarbonImmutable::now()))
                ))
                    return;

                $user = User::findOrFail($token->claims()->get('user_id'));
            } catch (Exception $e) {
                return;
            }
            return $user;//*/
        });
    }
}
