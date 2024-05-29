<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApplicationIdToMachinerySale extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('machinery_shop_characteristic', function (Blueprint $table) {
            $table->unsignedBigInteger('application_id')->nullable();
        });

        Schema::table('machinery_sale_requests', function (Blueprint $table) {

            $table->unsignedBigInteger('internal_number')->nullable();
            $table->string('currency')->nullable();

        });
        Schema::table('machinery_purchases', function (Blueprint $table) {

            $table->unsignedBigInteger('internal_number')->nullable();

        });

        Schema::table('machinery_sales', function (Blueprint $table) {

            $table->unsignedBigInteger('internal_number')->nullable();
            $table->string('status')->default(\Modules\Orders\Entities\Order::STATUS_ACCEPT);

        });

        Schema::table('company_branch_settings', function (Blueprint $table) {

            $table->unsignedBigInteger('last_machinery_sale_id')->default(1);

            $table->string('default_machinery_sale_contract_name')->nullable();
            $table->string('default_machinery_sale_contract_prefix')->nullable();
            $table->string('default_machinery_sale_contract_postfix')->nullable();
            $table->string('default_machinery_sale_application_url')->nullable();
            $table->string('default_machinery_sale_contract_url')->nullable();

        });

        Schema::table('machineries', function (Blueprint $table) {

            $table->unsignedBigInteger('selling_price')->default(0);
            $table->boolean('available_for_sale')->default(0);

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
