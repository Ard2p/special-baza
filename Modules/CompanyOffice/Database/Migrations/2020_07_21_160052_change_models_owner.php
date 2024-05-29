<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeModelsOwner extends Migration
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
        $tables = [
            'dispatcher_customers',
            'dispatcher_contractors',
            'dispatcher_contracts',
            'payments',
            'wialon_accounts',
            'entity_requisites',
            'individual_requisites'
        ];
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['contractor_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('contractor_id')->change();
        });


        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {

                $table->dropForeign(['user_id']);

                $table->renameColumn('user_id', 'creator_id');

            });
            Schema::table($table, function (Blueprint $table) {

                $table->unsignedInteger('creator_id')->nullable()->change();

                $table->unsignedBigInteger('company_branch_id');
            });
        }


        Artisan::call('assign_tables_to_branch');



        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {

                $table->foreign('creator_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');

                $table->foreign('company_branch_id')
                    ->references('id')
                    ->on('company_branches')
                    ->onDelete('cascade');
            });
        }

        Schema::table('orders', function (Blueprint $table) {

            $table->foreign('contractor_id')
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
        //
    }
}
