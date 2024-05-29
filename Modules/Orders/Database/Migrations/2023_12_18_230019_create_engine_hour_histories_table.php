<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEngineHourHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('engine_hour_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('value');
            $table->unsignedInteger('machinery_id');
            $table->nullableMorphs('owner');
            $table->timestamps();
        });

        Schema::table('engine_hour_histories', function (Blueprint $table) {
            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
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
        Schema::dropIfExists('engine_hour_histories');
    }
}
