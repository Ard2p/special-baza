<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceApplicationPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_application_pivot', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_component_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('order_duration');
            $table->string('order_type');
            $table->unsignedBigInteger('cost_per_unit');
            $table->unsignedBigInteger('delivery_cost')->default(0);
            $table->unsignedBigInteger('value_added')->default(0);
            $table->unsignedBigInteger('return_delivery')->default(0);
            $table->timestamps();
        });

        Schema::table('invoice_application_pivot', function (Blueprint $table) {

            $table->foreign('order_component_id')
                ->references('id')
                ->on('order_workers')
                ->onDelete('cascade');

            $table->foreign('invoice_id')
                ->references('id')
                ->on('dispatcher_invoices')
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
        Schema::dropIfExists('invoice_application_pivot');
    }
}
