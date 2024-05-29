<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('url');
            $table->string('name')->nullable();
            $table->unsignedBigInteger('order_component_id');
            $table->string('initiator_type')->nullable();
            $table->unsignedBigInteger('initiator_id')->nullable();
            $table->timestamps();
        });

        Schema::table('order_media', function (Blueprint $table) {
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
        Schema::dropIfExists('order_media');
    }
}
