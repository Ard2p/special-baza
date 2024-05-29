<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehiclePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicle_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cost_per_hour');
            $table->unsignedBigInteger('cost_per_shift');
            $table->string('type');
            $table->unsignedInteger('machinery_id');
        });

        Schema::table('vehicle_prices', function (Blueprint $table) {
            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('cascade');
        });

        foreach (\App\Machinery::query()->withTrashed()->get() as $vehicle) {
            $vehicle->prices()->save(
              new \Modules\ContractorOffice\Entities\Vehicle\Price([
                  'cost_per_hour' => $vehicle->sum_hour,
                  'cost_per_shift' => $vehicle->sum_day,
                  'type' => \Modules\ContractorOffice\Entities\Vehicle\Price::TYPE_CASHLESS_WITHOUT_VAT,
              ])
            );

            $vehicle->prices()->save(
                new \Modules\ContractorOffice\Entities\Vehicle\Price([
                    'cost_per_hour' => $vehicle->sum_hour,
                    'cost_per_shift' => $vehicle->sum_day,
                    'type' => \Modules\ContractorOffice\Entities\Vehicle\Price::TYPE_CASH,
                ])
            );
            $vehicle->prices()->save(
                new \Modules\ContractorOffice\Entities\Vehicle\Price([
                    'cost_per_hour' => $vehicle->sum_hour,
                    'cost_per_shift' => $vehicle->sum_day,
                    'type' => \Modules\ContractorOffice\Entities\Vehicle\Price::TYPE_CASHLESS_VAT,
                ])
            );
        }


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehicle_prices');
    }
}
