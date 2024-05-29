<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeys2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('work_hours', function (Blueprint $table) {
            $table->unsignedInteger('machine_id')->change();
            $table->foreign('machine_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('cascade');
        });

        Schema::table('contractor_timestamps', function (Blueprint $table) {
            $table->unsignedInteger('proposal_id')->change();
            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->onDelete('cascade');
        });

        Schema::table('machinery_timestamps', function (Blueprint $table) {
            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->onDelete('cascade');
        });


        Schema::table('attribute_machine', function (Blueprint $table) {
            $table->unsignedInteger('machinery_id')->change();
            $table->unsignedInteger('optional_attribute_id')->change();
            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('cascade');
            $table->foreign('optional_attribute_id')
                ->references('id')
                ->on('optional_attributes')
                ->onDelete('cascade');
        });

        Schema::disableForeignKeyConstraints();


        Schema::table('optional_attributes', function (Blueprint $table) {
            $table->unsignedInteger('type_id')->change();
            $table->foreign('type_id')
                ->references('id')
                ->on('types')
                ->onDelete('cascade');
        });

        Schema::table('optional_attribute_locales', function (Blueprint $table) {
            $table->unsignedInteger('optional_attribute_id')->change();
            $table->foreign('optional_attribute_id')
                ->references('id')
                ->on('optional_attributes')
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
        //
    }
}
