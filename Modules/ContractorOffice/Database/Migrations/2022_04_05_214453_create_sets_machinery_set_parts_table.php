<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSetsMachinerySetPartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('machinery_set_parts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('machinery_set_equipment_id');
            $table->unsignedBigInteger('part_id');
            $table->unsignedBigInteger('count');
            $table->timestamps();
        });

        Schema::table('machinery_set_parts', function (Blueprint $table) {
            $table->foreign('machinery_set_equipment_id')
                ->references('id')
                ->on('machinery_set_equipment')
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
        Schema::dropIfExists('machinery_set_parts');
    }
}
