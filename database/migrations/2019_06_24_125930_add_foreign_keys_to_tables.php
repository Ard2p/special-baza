<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('social_vkontakte_accounts', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('social_facebook_accounts', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('role_user', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('adverts', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('advert_send_email', function (Blueprint $table) {
            $table->unsignedInteger('advert_id')->change();
            $table->foreign('advert_id')
                ->references('id')
                ->on('adverts')
                ->onDelete('cascade');
        });


        Schema::table('advert_agents', function (Blueprint $table) {
            $table->unsignedInteger('advert_id')->change();
            $table->foreign('advert_id')
                ->references('id')
                ->on('adverts')
                ->onDelete('cascade');
        });


        Schema::table('advert_send_sms', function (Blueprint $table) {
            $table->unsignedInteger('advert_id')->change();
            $table->foreign('advert_id')
                ->references('id')
                ->on('adverts')
                ->onDelete('cascade');
        });


        Schema::table('advert_view_user', function (Blueprint $table) {
            $table->unsignedInteger('advert_id')->change();
            $table->foreign('advert_id')
                ->references('id')
                ->on('adverts')
                ->onDelete('cascade');
        });




        Schema::table('feedback', function (Blueprint $table) {
            $table->unsignedInteger('proposal_id')->change();
            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->onDelete('cascade');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->unsignedInteger('proposal_id')->change();
            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->onDelete('cascade');
        });


        Schema::table('machine_offer', function (Blueprint $table) {
            $table->unsignedInteger('offer_id')->change();
            $table->foreign('offer_id')
                ->references('id')
                ->on('offers')
                ->onDelete('cascade');
        });


        Schema::table('free_days', function (Blueprint $table) {
            $table->unsignedInteger('machine_id')->change();
            $table->foreign('machine_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('cascade');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
        Schema::table('widgets', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('widget_key_histories', function (Blueprint $table) {
            $table->unsignedInteger('widget_id')->change();
            $table->foreign('widget_id')
                ->references('id')
                ->on('widgets')
                ->onDelete('cascade');
        });


        Schema::table('widget_proposals', function (Blueprint $table) {
            $table->unsignedInteger('widget_id')->change();
            $table->foreign('widget_id')
                ->references('id')
                ->on('widgets')
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
        //
    }
}
