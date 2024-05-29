<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderComponetntToFreeDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('free_days', function (Blueprint $table) {
            $table->unsignedBigInteger('order_component_id')->nullable();
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
        Schema::table('free_days', function (Blueprint $table) {

        });
    }
}
