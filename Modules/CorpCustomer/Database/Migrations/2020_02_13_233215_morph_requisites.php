<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MorphRequisites extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

       Schema::table('entity_requisites', function (Blueprint $table){
           $table->dropColumn(['is_contractor']);
           $table->string('requisite_type');
           $table->unsignedInteger('requisite_id');
       });

        Schema::table('individual_requisites', function (Blueprint $table){
            $table->string('requisite_type');
            $table->string('gender')->nullable()->change();
            $table->unsignedInteger('requisite_id');
            $table->unsignedInteger('user_id')->change();
        });

        Schema::table('individual_requisites', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
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
