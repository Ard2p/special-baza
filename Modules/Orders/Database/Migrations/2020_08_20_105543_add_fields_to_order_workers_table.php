<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsToOrderWorkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_workers', function (Blueprint $table) {

            $table->unsignedBigInteger('application_id')->nullable();

            $table->boolean('complete')->default(false);

            $table->text('comment')->nullable();

        });

        Schema::table('orders', function (Blueprint $table) {

            $table->string('customer_type')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
        });

        Schema::table('dispatcher_customers', function (Blueprint $table) {
            $table->jsonb('options')->nullable();
            $table->unsignedInteger('contract_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_workers', function (Blueprint $table) {

        });
    }
}
