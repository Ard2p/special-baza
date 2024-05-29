<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OrderSoftDeletes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dispatcher_orders', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('individual_requisites', function (Blueprint $table) {
            $table->string('bank')->nulable();
            $table->string('bik')->nulable();
            $table->string('ks')->nulable();
            $table->string('rs')->nulable();
        });
        Schema::table('entity_requisites', function (Blueprint $table) {
            $table->string('register_address')->nulable();

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
