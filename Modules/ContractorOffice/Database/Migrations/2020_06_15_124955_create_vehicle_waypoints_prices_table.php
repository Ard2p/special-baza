<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehicleWaypointsPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('vehicle_waypoints_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->jsonb('distances')->nullable();
            $table->unsignedInteger('machinery_id');
        });

        Schema::table('vehicle_waypoints_prices', function (Blueprint $table) {
            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('cascade');
        });

        foreach (\App\Machinery::query()->withTrashed()->get() as $vehicle) {
            $vehicle->waypoints_price()->save(new \Modules\ContractorOffice\Entities\Vehicle\WaypointsPrice([]));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehicle\_waypoints_prices');
    }
}
