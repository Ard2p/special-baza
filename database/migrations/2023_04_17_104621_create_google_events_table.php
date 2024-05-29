<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoogleEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('google_events', function (Blueprint $table) {
            $table->id();
            $table->string('google_event_id');
            $table->integer('type');
            $table->morphs('eventable');
            $table->foreignId('company_branch_id')->constrained();
            $table->foreignId('google_calendar_id')->constrained();
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
        Schema::dropIfExists('google_events');
    }
}
