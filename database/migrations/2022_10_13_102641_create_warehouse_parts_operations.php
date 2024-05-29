<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehousePartsOperations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_parts_operations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_branches_warehouse_part_id');
            $table->unsignedBigInteger('order_worker_id');
            $table->string('type');
            $table->integer('count')->default(0);
            $table->double('cost_per_unit')->default(0);
            $table->timestamp('begin_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();

            $table->foreign('company_branches_warehouse_part_id','cp_warehouse_part_foreign')->references('id')->on('company_branches_warehouse_parts');
            $table->foreign('order_worker_id')->references('id')->on('order_workers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('warehouse_parts_operations');
    }
}
