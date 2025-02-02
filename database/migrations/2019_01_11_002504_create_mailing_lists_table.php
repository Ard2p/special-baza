<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMailingListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mailing_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('type');
            $table->integer('template_id');
            $table->timestamps();
        });

        Schema::create('mailing_phone', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mailing_list_id');
            $table->integer('phone_list_id');
            $table->timestamps();
        });

        Schema::create('mailing_email', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mailing_list_id');
            $table->integer('email_list_id');
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
        Schema::dropIfExists('mailing_lists');
        Schema::dropIfExists('mailing_phone');
        Schema::dropIfExists('mailing_email');
    }
}
