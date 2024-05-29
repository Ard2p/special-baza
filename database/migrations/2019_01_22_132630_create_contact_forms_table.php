<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_forms', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('button_text');
            $table->text('form_text');
            $table->text('url');
            $table->boolean('include_sub')->default(0);
            $table->string('position')->default('bottom');
            $table->boolean('collect_name')->default(0);
            $table->boolean('collect_email')->default(0);
            $table->boolean('collect_phone')->default(0);
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
        Schema::dropIfExists('contact_forms');
    }
}
