<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAvitoAddPriceToAvitoOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('avito_orders', function (Blueprint $table) {
            $table->unsignedInteger('avito_add_price')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('avito_orders', function (Blueprint $table) {
            $table->dropColumn('avito_add_price');
        });
    }
}
