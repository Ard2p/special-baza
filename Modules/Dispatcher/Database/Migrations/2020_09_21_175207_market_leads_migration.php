<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MarketLeadsMigration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dispatcher_customers_company_branches', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('company_branch_id');
        });


        Schema::create('dispatcher_lead_positions_machineries', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_position_id');
            $table->unsignedInteger('machinery_id');
        });

        Schema::table('dispatcher_customers_company_branches', function (Blueprint $table) {

            $table->foreign('customer_id')
                ->references('id')
                ->on('dispatcher_customers')
                ->onDelete('cascade');

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');
        });

        Schema::table('dispatcher_lead_positions_machineries', function (Blueprint $table) {

            $table->foreign('lead_position_id')
                ->references('id')
                ->on('lead_positions')
                ->onDelete('cascade');

            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('cascade');
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
