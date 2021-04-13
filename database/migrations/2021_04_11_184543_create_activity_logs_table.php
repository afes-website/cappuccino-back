<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamp('timestamp')->useCurrent();
            $table->string('exhibition_id');
            $table->string('guest_id');
            $table->string('log_type');
        });
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreign('guest_id')->references('id')->on('guests')
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
        Schema::dropIfExists('activity_logs');
    }
}
