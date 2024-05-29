<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWarehousePartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_parts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('vendor_code')->nullable();
            $table->unsignedInteger('brand_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedInteger('unit_id')->nullable();
            $table->jsonb('images')->nullable();
            $table->timestamps();
        });

        Schema::table('warehouse_parts', function (Blueprint $table) {

            $table->foreign('brand_id')
                ->references('id')
                ->on('brands')
                ->onDelete('set null');

            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('set null');
        });

        Schema::create('warehouse_parts_machinery_models', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('machinery_model_id');
            $table->unsignedBigInteger('part_id');
            $table->text('serial_numbers')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('warehouse\_parts');
    }
}
