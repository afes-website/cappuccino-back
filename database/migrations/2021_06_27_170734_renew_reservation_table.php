<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenewReservationTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('email');
            $table->renameColumn('people_count', 'member_all');
            $table->dropColumn('name');
            $table->dropColumn('address');
            $table->dropColumn('cellphone');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('email');
            $table->renameColumn('member_all', 'people_count');
            $table->string('name');
            $table->string('address');
            $table->string('cellphone');
        });
    }
}
