<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeFineAndBalanceHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fines', function (Blueprint $table) {
               $table->unsignedBigInteger('order_id')->nullable();
        });

        Schema::table('fines', function (Blueprint $table) {
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');
        });

        Schema::table('balance_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable();
        });

        Schema::table('balance_histories', function (Blueprint $table) {
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');
        });

        Schema::table('machineries', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_radius')->default(25);
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
