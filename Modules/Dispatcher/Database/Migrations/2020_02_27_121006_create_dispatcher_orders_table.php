<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDispatcherOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('dispatcher_leads_vehicles');
        Schema::dropIfExists('dispatcher_leads_contractors');

        Schema::create('dispatcher_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('customer_name');
            $table->string('phone');
            $table->string('address');
            $table->text('comment');
            $table->string('status');
            $table->timestamp('start_date')->nullable();
            $table->unsignedInteger('region_id');
            $table->unsignedInteger('city_id');
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('customer_id');
            $table->timestamps();
        });

        Schema::table('dispatcher_leads_orders', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->string('order_type');
        });

        Schema::table('dispatcher_orders', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('customer_id')
                ->references('id')
                ->on('dispatcher_customers')
                ->onDelete('cascade');
        });


        Schema::create('dispatcher_orders_contractors', function (Blueprint $table) {
            $table->unsignedBigInteger('dispatcher_order_id');
            $table->unsignedBigInteger('contractor_id');
            $table->unsignedBigInteger('sum')->default(0);
        });

        Schema::table('dispatcher_orders_contractors', function (Blueprint $table) {
            $table->foreign('dispatcher_order_id')
                ->references('id')
                ->on('dispatcher_orders')
                ->onDelete('cascade');

            $table->foreign('contractor_id')
                ->references('id')
                ->on('dispatcher_contractors')
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
        Schema::dropIfExists('dispatcher_orders');
    }
}
