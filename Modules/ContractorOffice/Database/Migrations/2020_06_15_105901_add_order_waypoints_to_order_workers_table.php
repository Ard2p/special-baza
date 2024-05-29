<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderWaypointsToOrderWorkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_workers', function (Blueprint $table) {
            $table->jsonb('waypoints')->nullable();
        });

        Schema::table('orders_need_type', function (Blueprint $table) {
            $table->jsonb('waypoints')->nullable();
        });

        Schema::table('lead_positions', function (Blueprint $table) {
            $table->jsonb('waypoints')->nullable();
        });




    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_workers', function (Blueprint $table) {

        });
    }
}
