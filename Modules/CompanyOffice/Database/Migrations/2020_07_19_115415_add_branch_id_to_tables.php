<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBranchIdToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('point', 'string');
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');

        \Illuminate\Support\Facades\Artisan::call('integrate_companies');

        Schema::disableForeignKeyConstraints();


        Schema::table('dispatcher_leads', function (Blueprint $table) {

            $table->unsignedBigInteger('company_branch_id');

            $table->dropForeign(['user_id']);

            $table->renameColumn('user_id','creator_id');
        });

        Schema::table('orders', function (Blueprint $table) {

            $table->unsignedBigInteger('company_branch_id');

            $table->dropForeign(['user_id']);

            $table->renameColumn('user_id','creator_id');
        });


        Schema::table('machineries', function (Blueprint $table) {

            $table->unsignedBigInteger('company_branch_id');

            $table->dropForeign(['user_id']);

            $table->renameColumn('user_id','creator_id');
        });

        Artisan::call('integrate_machines_leads_orders');

        Schema::table('machineries', function (Blueprint $table) {

            $table->unsignedInteger('creator_id')->nullable()->change();

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');
        });

        Schema::table('dispatcher_leads', function (Blueprint $table) {

            $table->unsignedInteger('creator_id')->nullable()->change();

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');
        });

        Schema::table('orders', function (Blueprint $table) {

            $table->unsignedInteger('creator_id')->nullable()->change();

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

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
        Schema::table('', function (Blueprint $table) {

        });
    }
}
