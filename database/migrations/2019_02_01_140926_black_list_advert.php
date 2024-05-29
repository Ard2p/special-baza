<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BlackListAdvert extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_links', function (Blueprint $table) {
            $table->integer('custom')->default(0);
        });

        Schema::table('sms_links', function (Blueprint $table) {
            $table->integer('custom')->default(0);
        });
        Schema::create('advert_black_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->integer('advert_id');
            $table->timestamps();
        });

        Schema::create('advert_send_sms', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('advert_id');
            $table->integer('sms_link_id');
            $table->integer('user_id');
            $table->timestamps();
        });
        Schema::create('advert_send_email', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('advert_id');
            $table->integer('email_link_id');
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::table('user_advert_proposal', function (Blueprint $table) {
            $table->renameColumn('user_id', 'advert_offer_id');
        });

        Schema::rename('agency_advert', 'advert_agents');

        Schema::table('advert_agents', function (Blueprint $table) {
            $table->integer('parent_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('email_links', function (Blueprint $table) {
            $table->dropColumn('custom');
        });

        Schema::table('sms_links', function (Blueprint $table) {
            $table->dropColumn('custom')->default(0);
        });
        Schema::dropIfExists('advert_black_list');
        Schema::dropIfExists('advert_send_sms');
        Schema::dropIfExists('advert_send_email');

        Schema::rename('advert_agents', 'agency_advert');

        Schema::table('advert_agents', function (Blueprint $table) {
            $table->dropColumn('parent_id');
        });
        Schema::table('user_advert_proposal', function (Blueprint $table) {
            $table->renameColumn('advert_offer_id', 'user_id');
        });
    }
}
