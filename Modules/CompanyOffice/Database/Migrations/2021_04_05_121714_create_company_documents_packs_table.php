<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyDocumentsPacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_documents_packs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('type_from');
            $table->string('type_to');
            $table->string('default_contract_url')->nullable();
            $table->string('default_application_url')->nullable();
            $table->string('default_disagreement_url')->nullable();
            $table->string('default_return_act_url')->nullable();
            $table->string('default_acceptance_act_url')->nullable();
            $table->string('default_cash_order')->nullable();
            $table->string('default_single_act_url')->nullable();
            $table->string('default_single_application_url')->nullable();

            $table->unsignedBigInteger('company_branch_id');
            $table->timestamps();
        });

        Schema::table('company_documents_packs', function (Blueprint $table) {

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
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
        Schema::dropIfExists('company_documents_packs');
    }
}
