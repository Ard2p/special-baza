<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimeoutCancelToAvitoOrderHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('avito_order_histories', function (Blueprint $table) {
            $table->tinyInteger('timeout_cancel')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('avito_order_histories', function (Blueprint $table) {
            $table->dropColumn('timeout_cancel');
        });
    }
}
