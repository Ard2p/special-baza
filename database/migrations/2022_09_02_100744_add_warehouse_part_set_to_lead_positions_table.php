<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarehousePartSetToLeadPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lead_positions', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_part_set_id')->nullable();
            $table->foreign('warehouse_part_set_id')->on('warehouse_part_sets')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lead_positions', function (Blueprint $table) {
            $table->dropForeign(['warehouse_part_set_id']);
            $table->dropColumn('warehouse_part_set_id');
        });
    }
}
