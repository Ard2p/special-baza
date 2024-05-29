<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemTariffUnitComparesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tariff_unit_compares', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name');
            $table->string('type');
            $table->unsignedInteger('amount');
            $table->unsignedBigInteger('company_branch_id');
        });

        Schema::create('tariff_grids', function (Blueprint $table) {

            $table->bigIncrements('id');


            $table->unsignedBigInteger('unit_compare_id');
            $table->unsignedInteger('min')->default(0);
            $table->unsignedInteger('max')->default(0);

            $table->boolean('is_fixed')->default(false);
            $table->unsignedInteger('market_markup')->default(0);

            $table->string('type')->default(\Modules\ContractorOffice\Entities\System\TariffGrid::WITH_DRIVER);

            $table->unsignedInteger('machinery_id');
            $table->unsignedInteger('sort_order')->default(0);

        });


        Schema::table('tariff_grids', function (Blueprint $table) {

            $table->foreign('unit_compare_id')
                ->references('id')
                ->on('tariff_unit_compares')
                ->onDelete('cascade');

            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('cascade');
        });

        Schema::create('tariff_grid_price', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('price')->default(0);
            $table->string('price_type');

            $table->unsignedBigInteger('tariff_grid_id');
        });

        Schema::table('tariff_grid_price', function (Blueprint $table) {

            $table->foreign('tariff_grid_id')
                ->references('id')
                ->on('tariff_grids')
                ->onDelete('cascade');
        });

        Schema::table('tariff_unit_compares', function (Blueprint $table) {

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
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
        Schema::dropIfExists('system\_tariff_unit_compares');
    }
}
