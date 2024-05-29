<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DomainsAlias extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('alias')->nullable();
        });
        Schema::table('countries', function (Blueprint $table) {
            $table->unsignedInteger('domain_id')->nullable();
        });

        Schema::table('countries', function (Blueprint $table) {

            $table->foreign('domain_id')
                ->references('id')
                ->on('domains')
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
        //
    }
}
