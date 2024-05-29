<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMachineryTypeToVehiclePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vehicle_prices', function (Blueprint $table) {
            $table->string('machinery_type')->default("App\\\Machinery");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vehicle_prices', function (Blueprint $table) {
            $table->dropColumn('machinery_type');
        });
    }
}
