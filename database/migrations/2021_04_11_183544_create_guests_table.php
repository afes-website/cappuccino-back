<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuestsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('guests', function (Blueprint $table) {
            $table->string('id');
            $table->primary('id');
            $table->timestamp('entered_at')->useCurrent();
            $table->timestamp('exited_at')->nullable();
            $table->string('reservation_id');
            $table->string('exhibition_id')->nullable();
            $table->string('term_id');
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->foreign('reservation_id')->references('id')->on('reservations')
                ->onUpdate('restrict')->onDelete('restrict');
            $table->foreign('term_id')->references('id')->on('terms')
                ->onUpdate('restrict')->onDelete('restrict');
            $table->foreign('exhibition_id')->references('id')->on('exhibitions')
                ->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('guests');
    }
}
