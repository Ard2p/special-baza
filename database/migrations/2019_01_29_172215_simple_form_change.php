<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SimpleFormChange extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('simple_proposals', function (Blueprint $table) {
            $table->string('comment_label')->nullable();
            $table->text('settings')->nullable();
            $table->boolean('is_publish')->default(1);
        });
        Schema::table('contact_forms', function (Blueprint $table) {
            $table->boolean('is_publish')->default(1);
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('simple_proposals', function (Blueprint $table) {
            $table->dropColumn('comment_label');
            $table->dropColumn('settings');
        });
        Schema::table('contact_forms', function (Blueprint $table) {
            $table->dropColumn('is_publish');
        });
    }
}
