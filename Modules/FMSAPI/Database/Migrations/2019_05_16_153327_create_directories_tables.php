<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDirectoriesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('pgsql')->create('regions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
        $regions = DB::table('regions')->select('id', 'name')->whereCountryId(1)->get();
        $all = [];
        foreach ($regions as $region){
            $all[] = [
                'id' => $region->id,
                'name' => $region->name,
            ];
        }
        DB::connection('pgsql')->table('regions')->insert($all);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('directories');
    }
}
