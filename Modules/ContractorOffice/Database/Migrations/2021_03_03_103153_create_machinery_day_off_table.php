<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMachineryDayOffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_schedule', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('day_of_week');
            $table->boolean('day_off')->default(false);
            $table->unsignedBigInteger('company_branch_id');

            $table->unique(['day_of_week', 'company_branch_id']);
        });

        Schema::table('company_schedule', function (Blueprint $table) {

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');

        });

        Schema::create('company_work_hours', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();

            $table->unsignedBigInteger('company_schedule_id')->nullable();
        });

        Schema::table('company_work_hours', function (Blueprint $table) {

            $table->foreign('company_schedule_id')
                ->references('id')
                ->on('company_schedule')
                ->onDelete('cascade');

        });

        Schema::create('company_day_offs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('company_branch_id');
        });

        Schema::table('company_day_offs', function (Blueprint $table) {

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
        Schema::dropIfExists('machinery_day_off');
    }
}
