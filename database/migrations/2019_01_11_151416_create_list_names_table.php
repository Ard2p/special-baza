<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateListNamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('mailing_phone');
        Schema::dropIfExists('mailing_email');

        Schema::create('list_names', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('type');
            $table->timestamps();
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->string('type');
        });

        Schema::create('list_name_phone', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('list_name_id');
            $table->integer('phone_list_id');
            $table->timestamps();
        });


        Schema::create('list_name_email', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('list_name_id');
            $table->integer('email_list_id');
            $table->timestamps();
        });

        Schema::create('list_name_mailing', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('list_name_id');
            $table->integer('mailing_list_id');
            $table->timestamps();
        });

        Schema::create('mailing_filters', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mailing_list_id');
            $table->integer('mailing_filter_id');
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
        Schema::dropIfExists('list_names');
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

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
