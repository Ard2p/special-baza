<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeShare extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('share_lists', function (Blueprint $table) {
            $table->boolean('is_watch')->default(0);
            $table->timestamp('watch_at')->nullable();
            $table->integer('confirm_status')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('share_lists', function (Blueprint $table) {
            $table->dropColumn('is_watch');
            $table->dropColumn('watch_at');
            $table->dropColumn('confirm_status');
        });
    }
}
