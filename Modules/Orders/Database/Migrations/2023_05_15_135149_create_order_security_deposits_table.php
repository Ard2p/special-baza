<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;

class CreateOrderSecurityDepositsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new DocumentsPack)->getTable(), function (Blueprint $table) {
            $table->string('default_pledge_invoice')->nullable();
            $table->string('default_pledge_invoice_with_stamp')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_security_deposits');
    }
}
