<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMachineType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('machineries', function (Blueprint $table) {
           $table->string('machine_type')->default('machine');
        });

        Schema::table('types', function (Blueprint $table) {
            $table->string('type')->default('machine');
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
            $table->dropColumn('machine_type');
        });
        Schema::table('types', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
