<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehicleTechnicalInspectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('machinery_technical_works', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('engine_hours')->nullable();
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('machinery_id');
            $table->unsignedInteger('creator_id')->nullable();
            $table->timestamps();
        });
        Schema::table('machinery_technical_works', function (Blueprint $table) {
            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('cascade');

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        Schema::table('free_days', function (Blueprint $table) {
           $table->unsignedBigInteger('technical_work_id')->nullable();
        });

        Schema::table('free_days', function (Blueprint $table) {

            $table->foreign('technical_work_id')
                ->references('id')
                ->on('machinery_technical_works')
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
        Schema::dropIfExists('vehicle\_technical_inspections');
    }
}
