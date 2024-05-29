<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Orders\Entities\OrderComponentActual;
use Modules\Orders\Entities\OrderComponentServiceActual;

class CreateOrderComponentServiceActualsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create((new OrderComponentServiceActual)->getTable(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_component_actual_id');
            $table->unsignedBigInteger('price');
            $table->string('name');
            $table->unsignedBigInteger('custom_service_id')->nullable();
            $table->unsignedInteger('value_added')->default(0)->nullable();
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();
        });

        Schema::table((new OrderComponentServiceActual)->getTable(), function (Blueprint $table) {

            $table->foreign('order_component_actual_id', 'actual_order_idx')
                ->references('id')
                ->on((new OrderComponentActual)->getTable())
                ->onDelete('cascade');

            $table->foreign('custom_service_id', 'actual_serv_idx')
                ->references('id')
                ->on('custom_services')
                ->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_component_service_actuals');
    }
}
