<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWidgetCountry extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('widgets', function (Blueprint $table){

          $table->unsignedInteger('country_id')->default(1);
          $table->string('locale')->default('ru');

      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('widgets', function (Blueprint $table){

            $table->dropColumn('country_id');
            $table->dropColumn('locale');

        });
    }
}
