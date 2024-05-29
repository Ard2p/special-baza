<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeSendingMail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sending_mails', function (Blueprint $table) {
            $table->dropColumn('watch_at');

        });
        Schema::table('sending_mails', function (Blueprint $table) {
            $table->timestamp('watch_at')->nullable();
        });

        Schema::table('contact_forms', function (Blueprint $table) {
            $table->integer('phone_template_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sending_mails', function (Blueprint $table) {
            //
        });
    }
}
