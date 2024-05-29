<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWarehousePartAnalogueGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_part_analogue_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        Schema::table('warehouse_parts', function (Blueprint $table) {
            $table->unsignedBigInteger('part_analogue_group_id')->nullable();
        });

        Schema::table('warehouse_parts', function (Blueprint $table) {

            $table->foreign('part_analogue_group_id')
                ->references('id')
                ->on('warehouse_part_analogue_groups')
                ->onDelete('set null');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('warehouse\_part_analogue_groups');
    }
}
