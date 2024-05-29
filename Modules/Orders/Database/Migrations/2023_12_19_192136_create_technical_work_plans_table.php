<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTechnicalWorkPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('technical_work_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('active');
            $table->string('type');
            $table->unsignedInteger('machinery_id')->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->time('duration')->nullable();
            $table->unsignedInteger('duration_between_works')->nullable();
            $table->unsignedInteger('duration_plan')->nullable();
        });

        Schema::table('technical_work_plans', function (Blueprint $table) {
            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('cascade');

            $table->foreign('category_id')
                ->references('id')
                ->on('types')
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
        Schema::dropIfExists('technical_work_plans');
    }
}
