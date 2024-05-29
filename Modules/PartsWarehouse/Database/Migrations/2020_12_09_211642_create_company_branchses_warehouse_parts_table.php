<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyBranchsesWarehousePartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_branches_warehouse_parts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_branch_id');
            $table->unsignedBigInteger('part_id');
        });

        Schema::table('company_branches_warehouse_parts', function (Blueprint $table) {

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');

            $table->foreign('part_id')
                ->references('id')
                ->on('warehouse_parts')
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
        Schema::dropIfExists('company_branchses_warehouse_parts');
    }
}
