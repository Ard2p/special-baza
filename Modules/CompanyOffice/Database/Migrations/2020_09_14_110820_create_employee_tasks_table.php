<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('description')->nullable();

            $table->string('status')->nullable();
            $table->boolean('important')->default(false);

            $table->timestamp('date_from')->nullable();
            $table->timestamp('date_to')->nullable();

            $table->unsignedInteger('creator_id')->nullable();
            $table->unsignedInteger('employee_id');

            $table->unsignedBigInteger('company_branch_id');
            $table->timestamps();
        });

        Schema::create('employee_tasks_binds', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_task_id');
            $table->unsignedBigInteger('bind_id');
            $table->string('bind_type');
        });

        Schema::table('employee_tasks', function (Blueprint $table) {

            $table->foreign('employee_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');


            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        Schema::table('employee_tasks_binds', function (Blueprint $table) {

            $table->foreign('employee_task_id')
                ->references('id')
                ->on('employee_tasks')
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
        Schema::dropIfExists('employee_tasks');
    }
}
