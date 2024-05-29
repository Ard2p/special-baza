<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dispatcher_customer_contracts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('prefix')->nullable();
            $table->unsignedInteger('number');
            $table->string('postfix')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->timestamps();
        });


        Schema::table('dispatcher_customer_contracts', function (Blueprint $table) {

            $table->foreign('customer_id')
                ->references('id')
                ->on('dispatcher_customers')
                ->onDelete('cascade');
        });

        Schema::table('order_workers', function (Blueprint $table) {
            $table->timestamps();
        });

        Schema::table('dispatcher_customers', function (Blueprint $table) {
            $table->dropColumn('contract_number');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dispatcher_customer_contracts');
    }
}
