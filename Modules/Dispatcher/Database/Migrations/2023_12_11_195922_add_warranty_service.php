<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Orders\Entities\Service\ServiceCenter;

class AddWarrantyService extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new ServiceCenter)->getTable(), function (Blueprint $table) {
            $table->boolean('is_warranty')->default(false);
        });

        Schema::table((new \App\Machinery)->getTable(), function (Blueprint $table) {
            $table->unsignedDecimal('engine_hours_after_tw')->default(0);
            $table->unsignedInteger('days_after_tw')->default(0);
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
