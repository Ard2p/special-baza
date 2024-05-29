<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contests', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('title')->nullable();
            $table->text('keywords')->nullable();
            $table->text('description');
            $table->string('photo')->nullable();
            $table->longText('content');
            $table->timestamp('from')->nullable();
            $table->timestamp('to')->nullable();

            $table->integer('type')->default(0);

            $table->timestamps();
        });


        Schema::create('contests_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('contest_id');
            $table->unsignedInteger('role_id');
        });

        Schema::create('contests_user_relations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('contest_id');
            $table->integer('user_relation_id');
            $table->decimal('coefficient')->default(1);
        });

        Schema::create('user_relations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('relation_access')->nullable();
            $table->string('name');
        });

        Schema::create('contests_voting', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('contest_id');
            $table->unsignedInteger('user_id');
        });

        Schema::create('contest_guest_voting', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ip');
            $table->integer('counter')->default(0);
            $table->integer('contest_id');
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('contest_id');
            $table->unsignedInteger('user_id');
            $table->integer('up')->default(0);
            $table->integer('down')->default(0);
            $table->integer('current_rate')->default(0);
            $table->string('photo')->nullable();
            $table->text('description')->nullable();
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
        Schema::dropIfExists('contests');
    }
}
