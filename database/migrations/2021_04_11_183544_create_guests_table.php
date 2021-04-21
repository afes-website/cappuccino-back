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
            $table->foreign('reservation_id')->references('id')->on('reservations')
                ->onUpdate('restrict')->onDelete('restrict');
            $table->string('exhibition_id')->nullable();
            $table->foreign('exhibition_id')->references('id')->on('exhibitions')
                ->onUpdate('restrict')->onDelete('restrict');
            $table->string('term_id');
            $table->foreign('term_id')->references('id')->on('terms')
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
