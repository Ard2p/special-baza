<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SubscribeTemplateNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscribe_templates', function (Blueprint $table) {
            $table->string('name');
        });

        Schema::table('sending_subscribes', function (Blueprint $table) {
            $table->integer('subscribe_template_id');
            $table->dropColumn('subscribe_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscribe_templates', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('sending_subscribes', function (Blueprint $table) {
            $table->dropColumn('subscribe_template_id');
            $table->integer('subscribe_id');
        });
    }
}
