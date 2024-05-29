<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeadStatusHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lead_status_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('lead_id');
            $table->string('old_status');
            $table->string('new_status');
            $table->timestamps();
        });
        Schema::table('leads', function (Blueprint $table) {
            $table->string('status')->default('lead');
            $table->integer('crm_company_id')->default(0);
        });
        Schema::create('crm_contracts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('number')->nullable();
            $table->string('crm_company_id')->default(0);
            $table->string('status')->nullable();
            $table->bigInteger('sum')->default(0);
            $table->timestamps();
        });
        Schema::create('crm_projects', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('number')->nullable();
            $table->string('crm_contract_id')->default(0);
            $table->timestamps();
        });
        Schema::create('crm_companies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->integer('region_id');
            $table->integer('city_id');
            $table->text('address')->nullable();
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
        Schema::dropIfExists('lead_status_histories');
    }
}
