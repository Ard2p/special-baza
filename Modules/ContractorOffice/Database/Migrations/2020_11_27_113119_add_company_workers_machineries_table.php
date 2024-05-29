<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCompanyWorkersMachineriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_workers_machinery', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('machinery_id');
            $table->unsignedBigInteger('company_worker_id');
        });

        Schema::table('company_workers_machinery', function (Blueprint $table) {


            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('cascade');

            $table->foreign('company_worker_id')
                ->references('id')
                ->on('company_workers')
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
        Schema::table('', function (Blueprint $table) {

        });
    }
}
