<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contact_forms', function (Blueprint $table) {
           $table->integer('template_id')->default(0);
           $table->text('settings')->nullable();
        });
        Schema::table('email_lists', function (Blueprint $table) {
            $table->string('name')->nullable();
        });
        Schema::table('phone_lists', function (Blueprint $table) {
            $table->string('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contact_forms', function (Blueprint $table) {
            $table->dropColumn('template_id');
            $table->dropColumn('settings');
        });
        Schema::table('email_lists', function (Blueprint $table) {
            $table->dropColumn('name');
        });
        Schema::table('phone_lists', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
}
