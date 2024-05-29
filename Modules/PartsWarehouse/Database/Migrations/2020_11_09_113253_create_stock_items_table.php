<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStockItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('part_id');
            $table->unsignedBigInteger('stock_id');
            $table->unsignedInteger('unit_id')->nullable();
            $table->unsignedBigInteger('amount')->default(0);
            $table->boolean('serial_accounting')->default(false);
        });

        Schema::table('stock_items', function (Blueprint $table) {

            $table->foreign('part_id')
                ->references('id')
                ->on('warehouse_parts')
                ->onDelete('cascade');

            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('set null');

            $table->foreign('stock_id')
                ->references('id')
                ->on('stocks')
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
        Schema::dropIfExists('stock\_items');
    }
}
