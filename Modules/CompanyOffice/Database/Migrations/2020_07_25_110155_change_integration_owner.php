<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIntegrationOwner extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('integrations_user', 'company_branches_integrations');

        Schema::table('company_branches_integrations', function (Blueprint $table) {
            $table->renameColumn('user_id', 'company_branch_id');

        });
        Schema::table('company_branches_integrations', function (Blueprint $table) {
            $table->unsignedBigInteger('company_branch_id')->change();
        });
        Schema::disableForeignKeyConstraints();

        Artisan::call('change_integration_owner');

        Schema::table('company_branches_integrations', function (Blueprint $table) {

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');
        });
        Schema::enableForeignKeyConstraints();
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
