<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMachineHide extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('machineries', function (Blueprint $table) {
           $table->boolean('is_hide')->default(0);
        });

        Schema::create('machine_show_user', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('machinery_id');
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('corp_company_id');
            $table->string('position')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('machineries', function (Blueprint $table) {
            $table->dropColumn('is_hide');
        });
        Schema::dropIfExists('machine_show_user');
        Schema::dropIfExists('employees');
    }
}
