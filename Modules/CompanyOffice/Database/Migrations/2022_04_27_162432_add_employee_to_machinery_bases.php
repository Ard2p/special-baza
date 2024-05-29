<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEmployeeToMachineryBases extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('machinery_bases', function (Blueprint $table) {
            $table->unsignedBigInteger('company_worker_id')->nullable();
        });

        Schema::table('machinery_bases', function (Blueprint $table) {

            $table->foreign('company_worker_id')
                ->references('id')
                ->on('company_workers')
                ->onDelete('set null');
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
