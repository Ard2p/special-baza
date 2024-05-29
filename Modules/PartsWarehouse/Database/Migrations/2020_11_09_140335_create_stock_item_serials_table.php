<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStockItemSerialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_item_serials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('serial');
            $table->unsignedBigInteger('item_id');
        });

        Schema::table('stock_item_serials', function (Blueprint $table) {

            $table->foreign('item_id')
                ->references('id')
                ->on('stock_items')
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
        Schema::dropIfExists('stock\_item_serials');
    }
}
