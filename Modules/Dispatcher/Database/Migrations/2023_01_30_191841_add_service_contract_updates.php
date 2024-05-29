<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\CompanyOffice\Entities\Company\CompanyBranchSettings;
use Modules\Dispatcher\Entities\Customer\CustomerContract;

class AddServiceContractUpdates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new CustomerContract)->getTable(), function (Blueprint $table) {
            $table->string('type')->default('rent');
        });

        Schema::table((new CompanyBranchSettings)->getTable(), function (Blueprint $table) {
            $table->string('contract_service_number_template')->default('{dd}/{lastInDay}-{mm}-{yy}');
            $table->string('contract_service_default_contract_prefix')->nullable();
            $table->string('contract_service_default_contract_postfix')->nullable();
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
