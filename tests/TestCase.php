<?php
namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Laravel\Lumen\Application;
use \DB;

abstract class TestCase extends \Laravel\Lumen\Testing\TestCase {

    private static $initialized = false;

    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication(): Application {
        return require __DIR__.'/../bootstrap/app.php';
    }

    public function setUp(): void {
        parent::setUp();

        if (self::$initialized === false) {
            if (env('TEST_FRESH_DB') === null && !file_exists(base_path() . '/.env.testing')) {
                // if config not specialized, abort
                fputs(STDERR, "testing settings not found. aborting for safe.\n");
                exit(1);
            }
            self::$initialized = true;
            if (env('TEST_FRESH_DB', true)) Artisan::call('migrate:fresh');
            else Artisan::call('migrate');
            Artisan::call('db:seed', [
                '--force' => true,
            ]);
        }
        DB::connection(null)->beginTransaction();
    }

    public function tearDown(): void {
        DB::connection(null)->rollBack();
        parent::tearDown();
    }
}
