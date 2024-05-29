<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWidgetRequestHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('widget_request_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('widget_id')->default(0);
            $table->string('widget_key')->nullable();
            $table->integer('success')->default(0);
            $table->integer('fail')->default(0);
            $table->text('referer')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('widget_request_histories');
    }
}
