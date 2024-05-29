<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SubmitFormChange extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submit_contact_forms', function (Blueprint $table) {
          $table->integer('sending_mail_id')->default(0);
          $table->integer('sending_sms_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submit_contact_forms', function (Blueprint $table) {
            $table->dropColumn('sending_mail_id');
            $table->dropColumn('sending_sms_id');
        });
    }
}
