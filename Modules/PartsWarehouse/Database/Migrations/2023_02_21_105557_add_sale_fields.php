<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSaleFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_branches_warehouse_parts', function (Blueprint $table) {
            $table->unsignedBigInteger('default_sale_cost_cashless')->default(0);
            $table->unsignedBigInteger('default_sale_cost_cashless_vat')->default(0);
            $table->string('name')->nullable();
            $table->string('vendor_code')->nullable();
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
