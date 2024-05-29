<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ins_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ins_tariff_id')->constrained();
            $table->timestamp('date_from');
            $table->timestamp('date_to');
            $table->double('price')->default(0);
            $table->boolean('active')->default(0);
            $table->string('contract_number')->nullable();
            $table->timestamp('contract_date')->nullable();
            $table->tinyInteger('contract_status')->nullable();
            $table->boolean('increase_rent_price');
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
        Schema::dropIfExists('ins_settings');
    }
}
