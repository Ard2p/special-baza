<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCompanySettingsFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_branch_settings', function (Blueprint $table) {
            $table->string('default_application_url')->nullable();
            $table->string('default_disagreement_url')->nullable();
            $table->string('default_return_act_url')->nullable();
            $table->string('default_acceptance_act_url')->nullable();
        });

        Schema::table('entity_requisites', function (Blueprint $table) {
            $table->string('director_short')->nullable();
            $table->string('director_genitive')->nullable();
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
