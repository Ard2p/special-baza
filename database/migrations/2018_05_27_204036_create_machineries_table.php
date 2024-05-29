<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMachineriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('machineries', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('region_id');
            $table->text('address');
            $table->string('photo');
            $table->integer('sum_hour');
            $table->integer('sum_day');
            $table->integer('type');
            $table->integer('brand_id');
            $table->string('psm_number');
            $table->string('name');
            $table->string('manufacturer');
            $table->string('certificate');
            $table->string('certificate_date');
            $table->text('issued_by');
            $table->text('checkup_by');
            $table->string('checkup_date');
            $table->string('year');
            $table->string('number');
            $table->string('engine');
            $table->string('transmission');
            $table->string('leading_bridge');
            $table->string('colour');
            $table->string('engine_power');
            $table->string('construction_weight');
            $table->string('construction_speed');
            $table->string('scans');
            $table->string('coordinates')->nullable();
            $table->text('comment');
            $table->softDeletes();
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
        Schema::dropIfExists('machineries');
    }
}
