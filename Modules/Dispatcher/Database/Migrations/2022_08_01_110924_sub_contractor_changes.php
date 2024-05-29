<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SubContractorChanges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dispatcher_customer_contracts', function (Blueprint $table) {

            $table->dropForeign(['customer_id']);

            $table->string('customer_type')->nullable();

        });

        Schema::table('dispatcher_contractors', function (Blueprint $table) {

            $table->unsignedBigInteger('last_application_id')->default(0);

        });

        Schema::table('order_workers', function (Blueprint $table) {

            $table->unsignedBigInteger('contractor_application_id')->default(0);

        });

        Schema::table('order_documents', function (Blueprint $table) {

            $table->string('owner_type')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
