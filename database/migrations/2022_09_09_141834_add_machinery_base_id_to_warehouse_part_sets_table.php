<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMachineryBaseIdToWarehousePartSetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('warehouse_part_sets', function (Blueprint $table) {
            $table->unsignedBigInteger('machinery_base_id')->nullable();
            $table->foreign('machinery_base_id')->references('id')->on('machinery_bases');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('warehouse_part_sets', function (Blueprint $table) {
            $table->dropForeign(['machinery_base_id']);
            $table->dropColumn('machinery_base_id');
        });
    }
}
