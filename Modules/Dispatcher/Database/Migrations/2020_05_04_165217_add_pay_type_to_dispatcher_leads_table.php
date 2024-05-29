<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPayTypeToDispatcherLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dispatcher_leads', function (Blueprint $table) {
               $table->string('pay_type')->default(\Modules\ContractorOffice\Entities\Vehicle\Price::TYPE_CASHLESS_WITHOUT_VAT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dispatcher_leads', function (Blueprint $table) {

        });
    }
}
