<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVehicleDeliveryCost extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('machineries', function (Blueprint $table) {
                $table->unsignedInteger('free_delivery_distance')->default(0);
                $table->unsignedInteger('delivery_cost_over')->default(0);
                $table->boolean('is_contractual_delivery')->default(0);
                $table->unsignedInteger('contractual_delivery_cost')->default(0);
        });

        Schema::table('vehicles_order', function (Blueprint $table){
            $table->unsignedInteger('delivery_cost')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
