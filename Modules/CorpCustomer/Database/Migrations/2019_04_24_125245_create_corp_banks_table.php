<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCorpBanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('corp_banks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->string('name');
            $table->string('account');
            $table->string('bik');
            $table->text('address');
            $table->timestamps();
        });
        Schema::create('banks_companies', function (Blueprint $table) {
              $table->bigIncrements('id');
              $table->unsignedInteger('corp_company_id');
              $table->unsignedInteger('corp_bank_id');
        });

        Schema::create('banks_brands', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('corp_brand_id');
            $table->unsignedInteger('corp_bank_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('corp_banks');
        Schema::dropIfExists('banks_companies');
        Schema::dropIfExists('banks_brands');
    }
}
