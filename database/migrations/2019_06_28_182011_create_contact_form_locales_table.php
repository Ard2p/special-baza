<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactFormLocalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_form_locales', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('button_text')->nullable();
            $table->text('form_text')->nullable();
            $table->string('comment_label')->nullable();
            $table->string('locale');
            $table->unsignedInteger('template_id');
            $table->unsignedInteger('contact_form_id');
            $table->timestamps();
        });

        Schema::table('contact_form_locales', function (Blueprint $table) {
            $table->foreign('contact_form_id')
                ->references('id')
                ->on('contact_forms')
                ->onDelete('cascade');
        });
        Schema::disableForeignKeyConstraints();
        Schema::table('list_names', function (Blueprint $table) {
            $table->unsignedInteger('contact_form_id')->change();
            $table->foreign('contact_form_id')
                ->references('id')
                ->on('contact_forms')
                ->onDelete('cascade');
        });

        Schema::table('list_name_email', function (Blueprint $table) {
            $table->unsignedInteger('list_name_id')->change();
            $table->foreign('list_name_id')
                ->references('id')
                ->on('list_names')
                ->onDelete('cascade');
        });

        Schema::table('list_name_phone', function (Blueprint $table) {
            $table->unsignedInteger('list_name_id')->change();
            $table->foreign('list_name_id')
                ->references('id')
                ->on('list_names')
                ->onDelete('cascade');
        });
        \Schema::enableForeignKeyConstraints();



    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_form_locales');
    }
}
