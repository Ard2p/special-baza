<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateManagerNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manager_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('text');
            $table->unsignedInteger('manager_id')->nullable();
            $table->unsignedBigInteger('owner_id');
            $table->string('owner_type');
            $table->timestamps();
        });

        Schema::table('manager_notes', function (Blueprint $table) {

            $table->foreign('manager_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manager_notes');
    }
}
