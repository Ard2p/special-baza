<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDefaultBaseToMachineriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('machineries', function (Blueprint $table) {
            $table->unsignedBigInteger('default_base_id')->nullable();
            $table->foreign('default_base_id')
                ->references('id')
                ->on('machinery_bases')->onDelete('set null');
        });

        Schema::table('individual_requisites', function (Blueprint $table) {
            $table->string('birth_place')->nullable();
        });

        Schema::table('company_cash_registers', function (Blueprint $table) {
            $table->unsignedInteger('creator_id')->nullable();

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('machineries', function (Blueprint $table) {

        });
    }
}
