<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSendingSmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sending_sms', function (Blueprint $table) {
            $table->increments('id');
            $table->string('phone_list_id')->default(0);
            $table->integer('template_id');
            $table->integer('confirm_status')->default(0);
            $table->boolean('is_watch')->default(0);
            $table->timestamp('watch_at')->nullable();
            $table->string('hash');
            $table->integer('contact_form_id')->default(0);
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
        Schema::dropIfExists('sending_sms');
    }
}
