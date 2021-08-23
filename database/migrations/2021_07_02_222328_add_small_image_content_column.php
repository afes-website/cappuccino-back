<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSmallImageContentColumn extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('images', function (Blueprint $table) {
            $table->binary('content_small');
        });

        if (env('DB_CONNECTION') == 'mysql')
            DB::statement('ALTER TABLE images MODIFY content_small LONGBLOB NOT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn('content_small');
        });
    }
}
