<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemTariffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('category_tariffs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('categories_tariffs', function (Blueprint $table) {
          $table->unsignedInteger('type_id');
          $table->unsignedBigInteger('tariff_id');
        });

        Schema::table('machineries', function (Blueprint $table) {
            $table->string('tariff_type')->default(\Modules\ContractorOffice\Entities\System\Tariff::TIME_CALCULATION);
        });


        Schema::table('categories_tariffs', function (Blueprint $table) {
            $table->foreign('type_id')
                ->references('id')
                ->on('types')
                ->onDelete('cascade');

            $table->foreign('tariff_id')
                ->references('id')
                ->on('category_tariffs')
                ->onDelete('cascade');
        });

        \Modules\ContractorOffice\Entities\System\Tariff::query()->insert([
            [

                'type' => \Modules\ContractorOffice\Entities\System\Tariff::TIME_CALCULATION,
            ],
            [

                'type' => \Modules\ContractorOffice\Entities\System\Tariff::DISTANCE_CALCULATION,
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
        Schema::dropIfExists('system\_tariffs');
    }
}
