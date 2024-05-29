<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserAccessBlocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
           $table->boolean('can_delete')->default(0);
        });

        Schema::create('access_blocks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('alias')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('roles_access_blocks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('access_block_id');
            $table->string('type');
        });

        Schema::table('roles_access_blocks', function (Blueprint $table) {
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
            $table->foreign('access_block_id')
                ->references('id')
                ->on('access_blocks')
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
        Schema::dropIfExists('user/_access_blocks');
    }
}
