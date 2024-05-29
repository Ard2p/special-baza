<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeRequisiteFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('entity_requisites', function (Blueprint $table) {
           $table->timestamp('last_update')->nullable();
           $table->string('status')->nullable();
        });

        Schema::table('individual_requisites', function (Blueprint $table) {

            $table->string('department_code')->nullable();
        });

        Schema::table('company_branches', function (Blueprint $table) {

            $table->boolean('active')->default(false);
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
