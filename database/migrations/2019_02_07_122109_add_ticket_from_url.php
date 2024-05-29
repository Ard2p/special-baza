<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTicketFromUrl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->integer('submit_ticket_popup_id')->default(0);
        });

        Schema::create('ticket_popups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('support_category_id')->nullable();
            $table->string('button_text');
            $table->text('form_text');
            $table->text('url');
            $table->boolean('include_sub')->default(0);
            $table->string('comment_label')->nullable();
            $table->text('settings')->nullable();
            $table->boolean('is_publish')->default(1);
            $table->timestamps();
        });

        Schema::create('submit_ticket_popups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('ticket_popup_id');
            $table->text('comment')->nullable();
            $table->text('url')->nullable();
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
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    }
}
