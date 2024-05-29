<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MarketingChange extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mailing_lists', function (Blueprint $table) {
            $table->integer('status')->default(0);
            $table->string('subject')->nullable();
            $table->longText('fields');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mailing_list', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('fields');
            $table->dropColumn('subject');
        });
    }
}
