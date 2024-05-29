<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueToTariffGridsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tariff_grids', function (Blueprint $table) {
            $table->dropForeign(['machinery_id']);
            $table->dropUnique('tariff_grids_machinery_id_min_unit_compare_id_unique');
            $table->unique(['machinery_id', 'min', 'unit_compare_id', 'type','machinery_type'],'unique_tariff');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tariff_grids', function (Blueprint $table) {
            $table->dropUnique(['machinery_id', 'min', 'unit_compare_id', 'type','machinery_type']);
            $table->unique(['machinery_id', 'min', 'unit_compare_id', 'type']);
        });
    }
}
