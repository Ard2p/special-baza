<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeInvoicesStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dispatcher_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('paid_sum')->default(0);
        });

        Schema::create('invoice_lead_pivot', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('invoice_id');
            $table->string('name');
            $table->unsignedBigInteger('order_duration');
            $table->timestamp('date_from')->nullable();
            $table->string('order_type');
            $table->string('vendor_code')->nullable();
            $table->unsignedBigInteger('cost_per_unit');
            $table->unsignedBigInteger('delivery_cost')->default(0);
            $table->unsignedBigInteger('return_delivery')->default(0);
        });

        Schema::table('invoice_lead_pivot', function (Blueprint $table) {

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
        //
    }
}
