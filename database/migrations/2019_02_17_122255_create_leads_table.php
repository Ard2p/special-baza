<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->default(0);
            $table->integer('manager_id')->default(0);
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->string('position')->nullable();
            $table->text('sites')->nullable();
            $table->text('socials')->nullable();
            $table->integer('is_contractor')->default(0);
            $table->integer('is_customer')->default(0);
            $table->timestamps();
        });
        Schema::create('lead_notes', function (Blueprint $table) {
            $table->increments('id');
            $table->text('note')->nullable();
            $table->string('note_type')->nullable();
            $table->text('attachments')->nullable();
            $table->timestamps();
        });

        Schema::create('lead_notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lead_id')->default(0);
            $table->integer('manager_id')->default(0);
            $table->integer('is_done')->default(0);
            $table->timestamp('start_from')->nullable();
            $table->timestamp('date')->nullable();
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
        Schema::dropIfExists('leads');
    }
}
