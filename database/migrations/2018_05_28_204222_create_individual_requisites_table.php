<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIndividualRequisitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('individual_requisites', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('firstname')->nullable();
            $table->string('middlename')->nullable();
            $table->string('surname')->nullable();
            $table->integer('gender')->nullable();
            $table->timestamp('birth_date')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('passport_date')->nullable();
            $table->text('issued_by')->nullable();
            $table->text('register_address')->nullable();
            $table->string('kp')->nullable();
            $table->string('scans')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('individual_requisites');
    }
}
