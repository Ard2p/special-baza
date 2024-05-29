<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToEmployeeTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_tasks', function (Blueprint $table) {
            $table->unsignedInteger('updated_by_id')->nullable();
            $table->unsignedInteger('responsible_id')->nullable();

            $table->foreign('updated_by_id')->references('id')->on('users');
            $table->foreign('responsible_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_tasks', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropForeign(['updated_by_id']);
            $table->dropForeign(['responsible_id']);

            $table->dropColumn('created_by_id');
            $table->dropColumn('updated_by_id');
            $table->dropColumn('responsible_id');
        });
    }
}
