<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSimpleProposalLocalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('simple_proposal_locales', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('simple_proposal_id');
            $table->string('button_text')->nullable();
            $table->string('locale')->nullable();
            $table->text('form_text')->nullable();
            $table->string('comment_label')->nullable();
            $table->timestamps();
        });

        Schema::table('simple_proposal_locales', function (Blueprint $table) {
            $table->foreign('simple_proposal_id')
                ->references('id')
                ->on('simple_proposals')
                ->onDelete('cascade');
        });
        Schema::disableForeignKeyConstraints();
        Schema::table('proposal_need_type', function (Blueprint $table) {
            $table->unsignedInteger('proposal_id')->change();
            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->onDelete('cascade');
        });

        Schema::table('invites', function (Blueprint $table) {
            $table->unsignedInteger('proposal_id')->change();
            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->onDelete('cascade');
        });

        Schema::table('entity_requisites', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
        Schema::table('article_locales', function (Blueprint $table) {
            $table->unsignedInteger('article_id')->change();
            $table->foreign('article_id')
                ->references('id')
                ->on('articles')
                ->onDelete('cascade');
        });
        \Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('simple_proposal_locales');
    }
}
