<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyBranchSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_branch_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('company_branch_id')->unique()->primary();
            $table->string('default_contract_name')->nullable();
            $table->string('default_contract_url')->nullable();
            $table->string('default_contract_prefix')->nullable();
            $table->string('default_contract_postfix')->nullable();
            $table->string('documents_head_image')->nullable();

        });

        Schema::table('company_branch_settings', function (Blueprint $table) {

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
        Schema::dropIfExists('company\_company_branch_settings');
    }
}
