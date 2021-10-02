<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenewGuestsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('guests', function (Blueprint $table) {
            $table->renameColumn('entered_at', 'registered_at');
            $table->renameColumn('exited_at', 'revoked_at');
            $table->boolean('is_spare')->after('id')->default(false);
            $table->boolean('is_force_revoked')->after('is_spare')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('guests', function (Blueprint $table) {
            $table->renameColumn('registered_at', 'entered_at');
            $table->renameColumn('revoked_at', 'exited_at');
            $table->dropColumn('is_spare');
            $table->dropColumn('is_force_revoked');
        });
    }
}
