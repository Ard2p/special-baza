<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_links', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('friends_list_id');
            $table->string('link');
            $table->integer('machine_id')->default(0);
            $table->integer('confirm_status')->default(0);
            $table->integer('is_watch')->default(0);
            $table->timestamp('watch_at')->nullable();
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
        Schema::dropIfExists('email_links');
    }
}
