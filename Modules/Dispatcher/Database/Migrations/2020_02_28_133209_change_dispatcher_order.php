<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDispatcherOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dispatcher_orders', function (Blueprint $table) {
                $table->boolean('is_paid')->default(0);
        });

        Schema::create('dispatcher_invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('number');
            $table->unsignedBigInteger('sum');
            $table->unsignedBigInteger('dispatcher_order_id');
            $table->unsignedBigInteger('requisite_id');
            $table->string('requisite_type');
            $table->unsignedBigInteger('main_requisite_id');
            $table->string('main_requisite_type');
            $table->boolean('is_paid')->default(0);
            $table->timestamps();
        });


        Schema::table('dispatcher_invoices', function (Blueprint $table) {
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
        //
    }
}
