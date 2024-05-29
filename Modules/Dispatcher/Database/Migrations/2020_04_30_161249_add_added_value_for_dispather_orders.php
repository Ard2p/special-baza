<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAddedValueForDispatherOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_workers', function (Blueprint $table) {
            $table->unsignedBigInteger('value_added')->default(0);
        });

        Schema::table('lead_offer_positions', function (Blueprint $table) {
            $table->unsignedBigInteger('value_added')->default(0);
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
