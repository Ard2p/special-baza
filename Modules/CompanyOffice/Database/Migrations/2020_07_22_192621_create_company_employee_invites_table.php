<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyEmployeeInvitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_employee_invites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email');
            $table->string('hash')->unique();
            $table->string('role');
            $table->unsignedBigInteger('company_branch_id');
            $table->timestamps();
        });

        Schema::table('company_employee_invites', function (Blueprint $table) {

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
        Schema::dropIfExists('company\_employee_invites');
    }
}
