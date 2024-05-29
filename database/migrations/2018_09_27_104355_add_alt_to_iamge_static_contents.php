<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAltToIamgeStaticContents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('static_contents', function (Blueprint $table) {
          $table->string('image_alt')->nullable();
        });
        Schema::table('articles', function (Blueprint $table) {
            $table->string('image_alt')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('static_contents', function (Blueprint $table) {
            $table->dropColumn('image_alt');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('image_alt');
        });
    }
}
