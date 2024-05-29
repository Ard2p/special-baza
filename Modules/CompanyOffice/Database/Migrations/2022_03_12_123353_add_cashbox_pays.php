<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCashboxPays extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_cash_registers', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_pay_id')->nullable();
            $table->foreign('invoice_pay_id')->references('id')
                ->on('invoice_pays')
                ->onDelete('cascade');
            $table->json('ref')->nullable();
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
