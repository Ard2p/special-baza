<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsRentToWarehousePartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_branches_warehouse_parts', function (Blueprint $table) {
            $table->boolean('is_rented')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_branches_warehouse_parts', function (Blueprint $table) {
            $table->dropColumn('is_rented');
        });
    }
}
