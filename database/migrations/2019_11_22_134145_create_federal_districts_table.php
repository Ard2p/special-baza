<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFederalDistrictsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('federal_districts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('country_id')->nullable();
            $table->timestamps();
        });

        Schema::table('regions', function (Blueprint $table) {
             $table->unsignedInteger('federal_district_id')->nullable();
        });

        Schema::table('regions', function (Blueprint $table) {
            $table->foreign('federal_district_id')
                ->references('id')
                ->on('federal_districts')
                ->onDelete('set null');

        });

        Schema::table('federal_districts', function (Blueprint $table) {
            $table->foreign('country_id')
                ->references('id')
                ->on('countries')
                ->onDelete('set null');
        });

        DB::table('federal_districts')->insert([
            [
              'name' => 'Южный ФО',
              'country_id' => 1,
            ],
            [
                'name' => 'Северо-Кавказский ФО',
                'country_id' => 1,
            ],
            [
                'name' => 'Приволжский ФО',
                'country_id' => 1,
            ],
            [
                'name' => 'Уральский ФО',
                'country_id' => 1,
            ],
            [
                'name' => 'Сибирский ФО',
                'country_id' => 1,
            ],
            [
                'name' => 'Дальневосточный ФО',
                'country_id' => 1,
            ],
            [
                'name' => 'Центральный ФО',
                'country_id' => 1,
            ],
            [
                'name' => 'Северо-Западный ФО',
                'country_id' => 1,
            ],
        ]);
    }



    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('federal_districts');
    }
}
