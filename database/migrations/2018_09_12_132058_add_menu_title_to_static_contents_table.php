<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMenuTitleToStaticContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('static_contents', function (Blueprint $table) {
            $table->string('menu_title')->nullable();
        });
        Schema::table('articles', function (Blueprint $table) {
            $table->integer('is_static')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('menu_title', function (Blueprint $table) {
            $table->dropColumn('menu_title');
        });
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('is_static');
        });
    }
}
