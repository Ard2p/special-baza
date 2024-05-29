<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachinerySale;
use Modules\Dispatcher\Entities\Customer\CustomerContract;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Service\ServiceCenter;

class CreateContractChanges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new CustomerContract())->getTable(), function (Blueprint $table) {
            $table->string('subject_type')->nullable()->default('contract');
            $table->boolean('is_active')->default(true);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
        });

        Schema::table((new ServiceCenter)->getTable(), function (Blueprint $table) {
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->foreign('contract_id')
                ->references('id')
                ->on((new CustomerContract())->getTable())
                ->nullOnDelete();
        });

        Schema::table((new Order())->getTable(), function (Blueprint $table) {
            $table->unsignedBigInteger('contract_id')->nullable();

            $table->foreign('contract_id')
                ->references('id')
                ->on((new CustomerContract())->getTable())
                ->nullOnDelete();
        });

        Schema::table((new MachinerySale())->getTable(), function (Blueprint $table) {
            $table->unsignedBigInteger('contract_id')->nullable();

            $table->foreign('contract_id')
                ->references('id')
                ->on((new CustomerContract())->getTable())
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('');
    }
}
