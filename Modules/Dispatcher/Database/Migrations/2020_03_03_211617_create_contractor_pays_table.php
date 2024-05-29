<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContractorPaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('dispatcher_orders_contractors');

        Schema::create('dispatcher_contractor_pays', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('type', ['card', 'cashless'])->default('card');
            $table->timestamp('date')->nullable();
            $table->unsignedBigInteger('sum');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('dispatcher_order_id')->nullable();
            $table->unsignedBigInteger('contractor_id');
            $table->string('contractor_type');
            $table->timestamps();
        });

        Schema::table('dispatcher_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('contractor_id');
            $table->unsignedBigInteger('contractor_sum');
        });

        Schema::table('dispatcher_orders', function (Blueprint $table) {
            $table->foreign('contractor_id')
                ->references('id')
                ->on('dispatcher_contractors')
                ->onDelete('cascade');
        });




        Schema::table('dispatcher_contractor_pays', function (Blueprint $table) {
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');


            $table->foreign('dispatcher_order_id')
                ->references('id')
                ->on('dispatcher_orders')
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
        Schema::dropIfExists('contractor_pays');
    }
}
