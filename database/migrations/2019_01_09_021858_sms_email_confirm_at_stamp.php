<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SmsEmailConfirmAtStamp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_links', function (Blueprint $table) {
            $table->timestamp('confirm_at')->nullable();
        });
        Schema::table('sms_links', function (Blueprint $table) {
            $table->timestamp('confirm_at')->nullable();
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
            $table->dropColumn('confirm_at');
        });
        Schema::table('sms_links', function (Blueprint $table) {
            $table->dropColumn('confirm_at');
        });
    }
}
