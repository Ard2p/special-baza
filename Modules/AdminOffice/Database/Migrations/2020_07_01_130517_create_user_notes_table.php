<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


        Schema::table('article_galleries', function (Blueprint $table) {
            $table->foreign('article_id')
                ->references('id')
                ->on('articles')
                ->onDelete('cascade');
        });

        Schema::create('user_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('text');
            $table->string('type');
            $table->jsonb('attachments')->nullable();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('manager_id')->nullable();
            $table->timestamps();
        });
        Schema::table('user_notes', function (Blueprint $table) {

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('manager_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user\_notes');
    }
}
