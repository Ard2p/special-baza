<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCategoryAvgMarketPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('category_avg_market_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cost_per_shift')->default(0);
            $table->unsignedBigInteger('cost_per_hour')->default(0);
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('country_id');
        });

        Schema::table('category_avg_market_prices', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')
                ->on('types')
                ->onDelete('cascade');

            $table->foreign('country_id')
                ->references('id')
                ->on('countries')
                ->onDelete('cascade');
        });

        Schema::table('machineries', function (Blueprint $table) {
            $table->boolean('price_includes_fas')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('category_avg_market_prices');
    }
}
