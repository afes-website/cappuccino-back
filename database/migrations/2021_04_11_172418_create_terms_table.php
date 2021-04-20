<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTermsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('terms', function (Blueprint $table) {
            $table->string('id');
            $table->primary('id');
            $table->timestamp('enter_scheduled_time')->default('2020-01-01 00:00:00');
            $table->timestamp('exit_scheduled_time')->default('2030-12-31 23:59:59');
            $table->string('guest_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('terms');
    }
}
