<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInternalNumbersInModel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $tables = [
            'orders',
            'dispatcher_pre_leads',
            'dispatcher_leads',
            'dispatcher_customers',
            'machineries',
        ];
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('internal_number')->nullable();
            });
        }

        /** @var \Modules\CompanyOffice\Entities\Company\CompanyBranch $branch */
        foreach (\Modules\CompanyOffice\Entities\Company\CompanyBranch::all() as $branch) {

            foreach ($branch->customers()->orderBy('created_at')->get() as $item) {
                $item->setInternalNumber();
            }

            foreach ($branch->leads()->orderBy('created_at')->get() as $item) {
                $item->setInternalNumber();
            }

            foreach ($branch->machines()->orderBy('created_at')->get() as $item) {
                $item->setInternalNumber();
            }

            foreach ($branch->preLeads()->orderBy('created_at')->get() as $item) {
                $item->setInternalNumber();
            }

            foreach ($branch->orders()->orderBy('created_at')->get() as $item) {
                $item->setInternalNumber();
            }
        }

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {

                $table->unique(['company_branch_id', 'internal_number']);
            });

        }

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
