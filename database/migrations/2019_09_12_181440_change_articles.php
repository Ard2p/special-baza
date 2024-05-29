<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeArticles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('articles', function (Blueprint $table) {
           $table->string('type')->nullable();
        });
        \Illuminate\Support\Facades\DB::table('articles')->where('is_news', 1)->update(['type' => 'news']);
        \Illuminate\Support\Facades\DB::table('articles')->where('is_article', 1)->update(['type' => 'article']);
        \Illuminate\Support\Facades\DB::table('articles')->where('is_static', 1)->update(['type' => 'content']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('articles', function (Blueprint $table) {
            //
        });
    }
}
