<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->text('message');
            $table->string('ip');
            $table->unsignedInteger('user_id')->default(0);
            $table->unsignedInteger('chat_id');
            $table->timestamps();
        });

        Schema::create('block_chat_user', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ip')->nullable();
            $table->unsignedInteger('user_id')->default(0);
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
        Schema::dropIfExists('chat_messages');
    }
}
