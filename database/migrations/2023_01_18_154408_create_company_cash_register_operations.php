<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyCashRegisterOperations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_cash_register_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_cash_register_id');
            $table->integer('sum');
            $table->tinyInteger('type');
            $table->unsignedBigInteger('invoice_pay_id')->nullable();
            $table->unsignedBigInteger('client_bank_setting_id')->nullable();
            $table->json('request')->nullable();
            $table->timestamps();

            $table->foreign('company_cash_register_id', 'operation_cash_register_index')->references('id')->on('company_cash_registers');
            $table->foreign('invoice_pay_id', 'operation_invoice_pay_index')->references('id')->on('invoice_pays');
            $table->foreign('client_bank_setting_id', 'operation_client_bank_index')->references('id')->on('client_bank_settings');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_cash_register_operations');
    }
}
