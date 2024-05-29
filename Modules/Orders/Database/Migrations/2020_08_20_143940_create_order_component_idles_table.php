<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderComponentIdlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_component_idles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamp('date_from')->nullable();
            $table->timestamp('date_to')->nullable();
            $table->unsignedBigInteger('order_worker_id');
        });

        Schema::table('order_component_idles', function (Blueprint $table) {
            $table->foreign('order_worker_id')
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
        Schema::dropIfExists('order_component_idles');
    }
}
