<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('email_confirm')->default(0);
            $table->integer('phone_confirm')->default(0);
            $table->integer('active')->default(1);
            $table->string('phone')->unique()->nullable();
            $table->string('email')->unique()->nullable();
            $table->integer('balance')->default(0);
            $table->string('account_type')->default('user');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
