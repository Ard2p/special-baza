<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderComponentActualsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_components_actual', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('order_component_id');

            $table->unsignedBigInteger('amount')->default(0);
            $table->unsignedBigInteger('cost_per_unit');
            $table->timestamp('date_from')->nullable();
            $table->timestamp('date_to')->nullable();
            $table->unsignedBigInteger('delivery_cost')->default(0);
            $table->string('order_type');
            $table->unsignedBigInteger('order_duration');
            $table->unsignedBigInteger('return_delivery')->default(0);
            $table->unsignedBigInteger('value_added')->default(0);


            $table->timestamps();
        });

        Schema::table('order_components_actual', function (Blueprint $table) {

            $table->foreign('order_component_id')
                ->references('id')
                ->on('order_workers')
                ->onDelete('cascade');
        });

        Schema::table('order_worker_report_timestamp', function (Blueprint $table) {

            $table->unsignedBigInteger('order_worker_report_id')->nullable()->change();

            $table->unsignedBigInteger('order_component_actual_id')->nullable();

            $table->foreign('order_component_actual_id')
                ->references('id')
                ->on('order_components_actual')
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
        Schema::dropIfExists('order_component_actuals');
    }
}
