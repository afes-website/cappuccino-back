<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExhibitionsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('exhibitions', function (Blueprint $table) {
            $table->string('id');
            $table->foreign('id')->references('id')->on('users')
                ->onUpdate('restrict')->onDelete('restrict');
            $table->primary('id');
            $table->string('name');
            $table->unsignedInteger('capacity');
            $table->string('room_id');
            $table->string('thumbnail_image_id')->nullable();
            $table->foreign('thumbnail_image_id')->references('id')->on('images')
                ->onUpdate('restrict')->onDelete('restrict');
            $table->timestamp('updated_at')->default('2000-01-01 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('exhibitions');
    }
}
