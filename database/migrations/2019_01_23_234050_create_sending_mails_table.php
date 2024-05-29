<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSendingMailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sending_mails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email_list_id')->default(0);
            $table->string('template_id');
            $table->integer('confirm_status')->default(0);
            $table->boolean('is_watch')->default(0);
            $table->timestamp('watch_at');
            $table->string('hash');
            $table->timestamps();
        });

        Schema::create('submit_contact_forms', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('email_list_id')->default(0);
            $table->integer('phone_list_id')->default(0);
            $table->string('name')->nullable();
            $table->string('comment')->nullable();
            $table->timestamps();
        });

        Schema::table('contact_forms', function (Blueprint $table) {
            $table->string('comment_label')->nullable();
            $table->boolean('collect_comment')->default(0);
        });

        Schema::table('email_lists', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('phone_lists', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sending_mails');
        Schema::dropIfExists('submit_contact_forms');

        Schema::table('email_lists', function (Blueprint $table) {
            $table->string('name')->nullable();
        });
        Schema::table('phone_lists', function (Blueprint $table) {
            $table->string('name')->nullable();
        });


        Schema::table('contact_forms', function (Blueprint $table) {
            $table->dropColumn('comment_label');
            $table->dropColumn('collect_comment');
        });
    }
}
