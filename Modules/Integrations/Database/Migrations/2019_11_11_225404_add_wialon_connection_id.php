<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWialonConnectionId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wialon_vehicles', function (Blueprint $table) {
            $table->unsignedInteger('wialon_connection_id');
            $table->dropForeign(['user_id']);
        });

        Schema::table('wialon_vehicles', function (Blueprint $table) {
            $table->dropColumn(['user_id']);
            $table->foreign('wialon_connection_id')
                ->references('id')
                ->on('wialon_accounts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
