<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsClientbankToCompanyCashRegisters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_cash_registers', function (Blueprint $table) {
            $table->boolean('is_clientbank')->default(0);
            $table->unsignedBigInteger('client_bank_setting_id')->nullable();
            $table->foreign('client_bank_setting_id','register_client_bank_index')->references('id')->on('client_bank_settings');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_cash_registers', function (Blueprint $table) {
            $table->dropForeign('register_client_bank_index');

            $table->dropColumn('client_bank_setting_id');
            $table->dropColumn('is_clientbank');
        });
    }
}
