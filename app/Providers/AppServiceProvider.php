<?php

namespace App\Providers;

use Dotenv\Dotenv;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    public function boot() {
        if (DB::getDriverName() == 'sqlite') {
            fputs(STDERR, "sqlite is no longer supported. use other database such as MySQL.\n");
            exit(1);
        }
    }
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        if (file_exists(base_path() . '/.env.' . $this->app->environment()))
            Dotenv::createMutable(base_path(), '.env.' . $this->app->environment())->load();
    }
}
