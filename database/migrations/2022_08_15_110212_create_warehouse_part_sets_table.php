<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehousePartSetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_part_sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->default('');
            $table->unsignedInteger('type_id');
            $table->unsignedBigInteger('company_branch_id');
            $table->timestamps();

            $table->foreign('type_id')->references('id')->on('types');
            $table->foreign('company_branch_id')->references('id')->on('company_branches');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('warehouse_part_sets');
    }
}
