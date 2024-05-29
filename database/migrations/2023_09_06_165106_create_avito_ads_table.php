<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvitoAdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('avito_ads', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('machinery_id');
            $table->integer('avito_id');
            $table->timestamps();

            $table->foreign('machinery_id')->references('id')->on('machineries');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('avito_ads');
    }
}
