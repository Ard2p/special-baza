<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInsuranceFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('machinery_bases', function (Blueprint $table) {

            $table->unsignedInteger('insurance_premium')->default(0);
        });

        Schema::table('machineries', function (Blueprint $table) {

            $table->unsignedBigInteger('insurance_premium_cost')->default(0);
        });

        Schema::table('company_slang_categories', function (Blueprint $table) {

            $table->unsignedInteger('insurance_premium')->default(0);
            $table->unsignedInteger('rent_days_count')->default(0);
            $table->unsignedInteger('service_days_count')->default(0);
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
