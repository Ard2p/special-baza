<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateValueAddedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('order_workers', function (Blueprint $table) {
            $table->dropColumn('value_added');
        });

        Schema::create('order_workers_value_added', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('order_id');
            $table->string('worker_type');
            $table->unsignedBigInteger('worker_id');
            $table->unsignedInteger('owner_id');
        });

        Schema::table('order_workers_value_added', function (Blueprint $table) {
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');
            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('value_addeds');
    }
}
