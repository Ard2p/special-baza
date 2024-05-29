<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeContractorPaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::dropIfExists('dispatcher_vehicles');

        Schema::table('dispatcher_contractor_pays', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn(['order_id']);
            $table->unsignedBigInteger('order_worker_id');
            $table->foreign('order_worker_id')
                ->references('id')
                ->on('order_workers')
                ->onDelete('cascade');
        });

        Schema::table('order_workers', function (Blueprint $table) {
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
        //
    }
}
