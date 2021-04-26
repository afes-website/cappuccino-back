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
            $table->timestamp('entered_at')->default('2000-01-01 00:00:00');
            $table->timestamp('exited_at')->nullable()->default(null);
            $table->string('reservation_id')->index();
            $table->foreign('reservation_id')->references('id')->on('reservations')
                ->onUpdate('restrict')->onDelete('restrict');
            $table->string('exhibition_id')->nullable()->index();
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
