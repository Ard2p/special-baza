<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTelematicDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telematic_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('telematic_vehicle_id')->default(0);
            $table->decimal('average_speed')->default(0);
            $table->decimal('max_speed')->default(0);
            $table->decimal('average_fuel')->default(0);

            $table->unsignedBigInteger('begin_mileage')->default(0);
            $table->unsignedBigInteger('end_mileage')->default(0);
            $table->unsignedBigInteger('mileage')->default(0);

            $table->decimal('mileage_by_work_hours')->default(0);
            $table->time('working_hours')->nullable();
            $table->time('time_in_motion')->nullable();

            $table->decimal('toll_roads_mileage')->default(0);
            $table->decimal('toll_roads_cost')->default(0);
            $table->decimal('fuel_level_begin')->default(0);
            $table->decimal('fuel_level_end')->default(0);
            $table->decimal('fuel_consumption_abs')->default(0);
            $table->decimal('fuel_consumption_fls')->default(0);
            $table->decimal('fuel_consumption_ins')->default(0);
            $table->decimal('fuel_drain')->default(0);
            $table->decimal('fuel_drain_count')->default(0);

            $table->string('driver')->nullable();
            $table->string('telematic_type')->default('wialon');


            $table->timestamp('period_from')->nullable();
            $table->timestamp('period_to')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('telematic_data');
    }
}
