<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id', 16);
            $table->primary('id');
            $table->string('name');
            $table->string('password');
            $table->boolean("perm_admin");
            $table->boolean("perm_exhibition");
            $table->boolean("perm_executive");
            $table->boolean("perm_reservation");
            $table->boolean("perm_teacher");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('users');
    }
}
