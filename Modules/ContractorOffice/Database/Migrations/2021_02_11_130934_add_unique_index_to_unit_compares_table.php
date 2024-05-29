<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUniqueIndexToUnitComparesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


        Schema::table('tariff_unit_compares', function (Blueprint $table) {
            $table->unique(['company_branch_id', 'amount', 'type']);
        });

        Schema::table('tariff_grids', function (Blueprint $table) {
            $table->unique(['machinery_id', 'min', 'unit_compare_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unit_compares', function (Blueprint $table) {

        });
    }
}
