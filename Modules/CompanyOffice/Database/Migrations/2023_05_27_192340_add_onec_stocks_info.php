<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\PartsWarehouse\Entities\Stock\Stock;

class AddOnecStocksInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new Stock())->getTable(), function (Blueprint $table) {
            $table->jsonb('onec_info')->nullable();
        });

        Schema::table((new DispatcherInvoice())->getTable(), function (Blueprint $table) {
            $table->jsonb('onec_release_info')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
