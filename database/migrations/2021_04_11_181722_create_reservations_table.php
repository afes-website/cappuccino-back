<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('reservations', function (Blueprint $table) {
            $table->string('id');
            $table->primary('id');
            $table->string('email');
            $table->integer('people_count');
            $table->string('term_id');
            $table->foreign('term_id')->references('id')->on('users')
                ->onUpdate('restrict')->onDelete('restrict');
            $table->string('name');
            $table->string('address');
            $table->string('cellphone');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('reservations');
    }
}
