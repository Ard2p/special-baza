<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderComponentServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_worker_services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_component_id');
            $table->unsignedBigInteger('price');
            $table->string('name');
        });


        Schema::table('order_worker_services', function (Blueprint $table) {
            $table->foreign('order_component_id')
                ->references('id')
                ->on('order_workers')
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
