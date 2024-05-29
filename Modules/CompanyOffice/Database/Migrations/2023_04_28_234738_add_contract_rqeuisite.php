<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Dispatcher\Entities\Customer\CustomerContract;

class AddContractRqeuisite extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new CustomerContract())->getTable(), function (Blueprint $table) {
            $table->nullableMorphs('requisite');
            $table->unsignedBigInteger('last_application_id')->default(0);
        });

        Schema::table((new \App\User\EntityRequisite())->getTable(), function (Blueprint $table) {
            $table->string('contract_number_template')->nullable();
            $table->string('contract_sale_number_template')->nullable();
            $table->string('contract_service_number_template')->nullable();
            $table->string('contract_default_name')->nullable();
            $table->string('contract_service_default_name')->nullable();
        });

        Schema::table((new \App\User\IndividualRequisite())->getTable(), function (Blueprint $table) {
            $table->string('contract_number_template')->nullable();
            $table->string('contract_sale_number_template')->nullable();
            $table->string('contract_service_number_template')->nullable();
            $table->string('contract_default_name')->nullable();
            $table->string('contract_service_default_name')->nullable();
            $table->string('position')->nullable();
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
