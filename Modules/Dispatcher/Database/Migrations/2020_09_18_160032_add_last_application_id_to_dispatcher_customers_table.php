<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLastApplicationIdToDispatcherCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dispatcher_customers', function (Blueprint $table) {
            $table->unsignedBigInteger('last_application_id')->default(0);
        });
        foreach (\Modules\Dispatcher\Entities\Customer::all() as $customer) {
            $customer->update([
                'last_application_id' => $customer->lastApplicationId()
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dispatcher_customers', function (Blueprint $table) {

        });
    }
}
