<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehousePartSetPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_part_set_positions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('warehouse_part_set_id');
            $table->unsignedBigInteger('cb_warehouse_part_id');
            $table->integer('count');
            $table->timestamps();

            $table->foreign('warehouse_part_set_id')->references('id')->on('warehouse_part_sets');
            $table->foreign('cb_warehouse_part_id','company_branches_warehouse_part_id_foreign')->references('id')->on('company_branches_warehouse_parts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('warehouse_part_set_positions');
    }
}
