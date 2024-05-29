<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDispatcherContractor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dispatcher_contractors', function (Blueprint $table) {
            $table->unsignedInteger('region_id');
            $table->unsignedInteger('city_id');
        });

        Schema::table('dispatcher_vehicles', function (Blueprint $table) {
            $table->dropColumn('city_id');
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
