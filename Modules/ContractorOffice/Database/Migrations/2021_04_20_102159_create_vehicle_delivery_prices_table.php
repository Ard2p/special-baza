<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehicleDeliveryPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('machinery_delivery_tariff_grid', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('min')->default(0);
            $table->boolean('is_fixed')->default(false);
            $table->string('type');
            $table->unsignedInteger('machinery_id');

        });

        Schema::table('machinery_delivery_tariff_grid', function (Blueprint $table) {


            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('cascade');
        });

        Schema::create('machinery_delivery_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('price')->nullable();
            $table->string('price_type');
            $table->unsignedBigInteger('delivery_tariff_grid_id');
        });

        Schema::table('machinery_delivery_prices', function (Blueprint $table) {


            $table->foreign('delivery_tariff_grid_id')
                ->references('id')
                ->on('machinery_delivery_tariff_grid')
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
        Schema::dropIfExists('machinery_delivery_prices');
    }
}
