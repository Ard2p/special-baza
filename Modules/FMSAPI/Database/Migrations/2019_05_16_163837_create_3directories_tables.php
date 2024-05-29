<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Create3directoriesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('pgsql')->create('brands', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::connection('pgsql')->create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('type');
        });

        $brands = \App\Machines\Brand::all();
        $all = [];
        foreach ($brands as $brand) {
            $all[] = [
                'id' => $brand->id,
                'name' => $brand->name,
            ];
        }
        DB::connection('pgsql')->table('brands')->insert($all);

        $categories = \App\Machines\Type::all();
        $all = [];
        foreach ($categories as $category) {
            $all[] = [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
            ];
        }
        DB::connection('pgsql')->table('categories')->insert($all);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('3directories');
    }
}
