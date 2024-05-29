<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsInvoicePaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_pays', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');
            $table->date('date')->nullable();
            $table->unsignedBigInteger('sum');
            $table->unsignedInteger('tax_percent');
            $table->unsignedBigInteger('tax');
            $table->unsignedBigInteger('invoice_id');
            $table->timestamps();
        });

        Schema::table('invoice_pays', function (Blueprint $table) {
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
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
        Schema::dropIfExists('payments\_invoice_pays');
    }
}
