<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMachinerySetsOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('machinery_sets_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('machinery_set_id');
            $table->unsignedInteger('count');
            $table->json('prices');
            $table->timestamps();
        });

        Schema::table('order_workers', function (Blueprint $table) {
            $table->unsignedBigInteger('machinery_sets_order_id')->nullable();
            $table->foreign('machinery_sets_order_id')
                ->references('id')
                ->on('machinery_sets_orders')
                ->onDelete('set null');
        });

        Schema::table('machinery_sets_orders', function (Blueprint $table) {

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');

            $table->foreign('machinery_set_id')
                ->references('id')
                ->on('machinery_sets')
                ->onDelete('cascade');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['machinery_set_id']);
            $table->dropColumn([
                'set_prices',
                'machinery_set_id',
            ]);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('machinery_sets_orders');
    }
}
