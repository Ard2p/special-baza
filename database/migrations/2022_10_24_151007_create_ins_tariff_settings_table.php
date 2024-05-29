<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsTariffSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ins_tariff_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('market_price_min')->nullable();
            $table->integer('market_price_max')->nullable();
            $table->integer('rent_days_count')->nullable();
            $table->integer('repair_count')->nullable();
            $table->double('one_compensation_percent')->nullable();
            $table->double('all_compensations_percent')->nullable();
            $table->double('franchise_total')->nullable();
            $table->double('franchise_repair')->nullable();
            $table->double('b2b_tariff_1_5')->nullable();
            $table->double('b2b_tariff_5_21')->nullable();
            $table->double('b2b_tariff_21_60')->nullable();
            $table->double('b2b_tariff_60')->nullable();
            $table->double('b2c_tariff_1_5')->nullable();
            $table->double('b2c_tariff_5_21')->nullable();
            $table->double('b2c_tariff_21_60')->nullable();
            $table->double('b2c_tariff_60')->nullable();
            $table->foreignId('company_branch_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ins_tariff_settings');
    }
}
