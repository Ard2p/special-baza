<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Dispatcher\Entities\DispatcherInvoice;

class AddPayDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new CompanyBranch)->getTable(), function (Blueprint $table) {
            $table->unsignedInteger('invoice_pay_days_count')->nullable();
        });

        Schema::table((new DispatcherInvoice())->getTable(), function (Blueprint $table) {
            $table->unsignedInteger('invoice_pay_days_count')->nullable();
            $table->timestamp('paid_date')->nullable();
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
