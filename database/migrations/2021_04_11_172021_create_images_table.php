<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateImagesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('images', function (Blueprint $table) {
            $table->string('id');
            $table->primary('id');
            $table->binary('content');
            $table->string('user_id');
            $table->string('mime_type');
            $table->timestamp('created_at')->useCurrent();
        });

        if (env('DB_CONNECTION') == 'mysql')
            DB::statement('ALTER TABLE images MODIFY content LONGBLOB NOT NULL');

        Schema::table('images', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('images');
    }
}
