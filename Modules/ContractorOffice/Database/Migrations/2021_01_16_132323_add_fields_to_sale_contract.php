<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsToSaleContract extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_sale_contracts', function (Blueprint $table) {
            $table->string('prefix')->nullable();
            $table->string('postfix')->nullable();
            $table->unsignedBigInteger('number')->nullable();
        });

        Schema::table('machinery_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id');
            $table->string('customer_type');
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
