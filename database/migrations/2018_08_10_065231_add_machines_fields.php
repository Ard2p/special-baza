<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMachinesFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('machineries', function (Blueprint $table) {
            $table->integer('city_id')->nullable();
            $table->string('act_number')->nullable();
            $table->string('act_date')->nullable();
            $table->string('act_year')->nullable();
            $table->string('psm_manufacturer_number')->nullable();
            $table->string('engine_type')->nullable();
            $table->string('dimensions')->nullable();
            $table->string('year_release')->nullable();
            $table->string('owner')->nullable();
            $table->string('basis_for_witness')->nullable();
            $table->string('witness_date')->nullable();
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
