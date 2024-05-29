<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Create2directoriesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('pgsql')->create('cities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('region_id');
        });
        Schema::connection('pgsql')->table('cities', function (Blueprint $table) {
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
        });
        $cities = \App\City::whereHas('region', function ($q) {
            return $q->where('country_id', 1);
        })->get();
        $all = [];
        foreach ($cities as $city) {
            $all[] = [
                'id' => $city->id,
                'name' => $city->name,
                'region_id' => $city->region_id
            ];
        }
        DB::connection('pgsql')->table('cities')->insert($all);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('2directories');
    }
}
