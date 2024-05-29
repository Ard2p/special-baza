<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrmCompanyRequisitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_company_requisites', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('crm_company_id');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('inn')->nullable();
            $table->string('kpp')->nullable();
            $table->string('ogrn')->nullable();
            $table->string('director')->nullable();
            $table->string('booker')->nullable();
            $table->string('bank')->nullable();
            $table->string('bik')->nullable();
            $table->string('ks')->nullable();
            $table->string('rs')->nullable();
            $table->boolean('active')->deafult(0);
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
        Schema::dropIfExists('crm_company_requisites');
    }
}
