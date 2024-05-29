<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeShopRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_parts_requests_positions', function (Blueprint $table) {

            $table->dropForeign(['item_id']);
            $table->dropColumn(['item_id']);

            $table->unsignedBigInteger('part_id');
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
        //
    }
}
