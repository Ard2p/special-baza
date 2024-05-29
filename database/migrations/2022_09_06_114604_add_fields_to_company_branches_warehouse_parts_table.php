<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToCompanyBranchesWarehousePartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_branches_warehouse_parts', function (Blueprint $table) {
            $table->integer('min_order')->nullable();
            $table->string('min_order_type')->nullable();
            $table->string('change_hour')->nullable();
            $table->string('currency')->default('RUB');
            $table->string('tariff_type')->default('time_calculation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_branches_warehouse_parts', function (Blueprint $table) {
            $table->dropColumn('min_order');
            $table->dropColumn('min_order_type');
            $table->dropColumn('change_hour');
            $table->dropColumn('currency');
            $table->dropColumn('tariff_type');
        });
    }
}
