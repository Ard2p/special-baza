<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTbcBalanceHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbc_balance_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('admin_id')->default(0);
            $table->integer('old_sum');
            $table->integer('new_sum');
            $table->string('type')->nullable();
            $table->integer('sum');
            $table->string('reason')->nullable();
            $table->integer('email_link_id')->default(0);
            $table->integer('sms_link_id')->default(0);
            $table->timestamps();
        });
        Schema::table('email_links', function (Blueprint $table) {
            $table->string('hash')->nullable();
        });
        Schema::table('sms_links', function (Blueprint $table) {
            $table->string('hash')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbc_balance_histories');
        Schema::table('email_links', function (Blueprint $table) {
            $table->dropColumn('hash');
        });
        Schema::table('sms_links', function (Blueprint $table) {
            $table->dropColumn('hash');
        });
    }
}
