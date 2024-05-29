<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsToServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_centers', function (Blueprint $table) {
            $table->unsignedInteger('creator_id')->nullable();

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->timestamp('date_from')->nullable();
            $table->timestamp('date_to')->nullable();

            $table->unsignedBigInteger('documents_pack_id')->nullable();
            $table->nullableMorphs('contractor_requisite', 'contractor_requisite');
            $table->foreign('documents_pack_id')
                ->references('id')
                ->on('company_documents_packs')
                ->onDelete('set null');

            $table->unsignedBigInteger('base_id')->nullable();
            $table->foreign('base_id')
                ->references('id')
                ->on('machinery_bases')
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
        Schema::table('', function (Blueprint $table) {

        });
    }
}
