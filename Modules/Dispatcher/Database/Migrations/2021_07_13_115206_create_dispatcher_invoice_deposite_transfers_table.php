<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDispatcherInvoiceDepositeTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dispatcher_invoice_deposit_transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('donor_invoice_id');
            $table->unsignedBigInteger('current_invoice_id');
            $table->unsignedBigInteger('sum');
            $table->timestamps();
        });
        Schema::table('dispatcher_invoice_deposit_transfers', function (Blueprint $table) {
            $table->foreign('donor_invoice_id')
                ->references('id')
                ->on('dispatcher_invoices')
                ->onDelete('cascade');

            $table->foreign('current_invoice_id')
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
        Schema::dropIfExists('dispatcher_invoice_deposit_transfers');
    }
}
