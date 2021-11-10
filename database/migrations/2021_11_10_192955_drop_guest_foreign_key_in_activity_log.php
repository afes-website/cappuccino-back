<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropGuestForeignKeyInActivityLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropForeign('activity_log_guest_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->foreign('guest_id')->references('id')->on('guests')
                ->onUpdate('restrict')->onDelete('restrict');
        });
    }
}
