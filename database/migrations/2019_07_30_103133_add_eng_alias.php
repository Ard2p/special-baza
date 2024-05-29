<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEngAlias extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('types', function (Blueprint $table) {
           $table->string('eng_alias')->nullable();
        });
        Schema::disableForeignKeyConstraints();
        Schema::table('type_locales', function (Blueprint $table) {
            $table->unsignedInteger('type_id')->change();
        });

        Schema::table('type_locales', function (Blueprint $table) {
            $table->foreign('type_id')
                ->references('id')
                ->on('types')
                ->onDelete('cascade');
        });

        Schema::table('city_locales', function (Blueprint $table) {
            $table->unsignedInteger('city_id')->change();
        });

        Schema::table('city_locales', function (Blueprint $table) {
            $table->foreign('city_id')
                ->references('id')
                ->on('cities')
                ->onDelete('cascade');
        });

        Schema::table('city_codes', function (Blueprint $table) {
            $table->unsignedInteger('city_id')->change();
        });

        Schema::table('city_codes', function (Blueprint $table) {
            $table->foreign('city_id')
                ->references('id')
                ->on('cities')
                ->onDelete('cascade');
        });

        Schema::table('gibdd_codes', function (Blueprint $table) {
            $table->unsignedInteger('region_id')->change();
        });

        Schema::table('gibdd_codes', function (Blueprint $table) {
            $table->foreign('region_id')
                ->references('id')
                ->on('regions')
                ->onDelete('cascade');
        });

        Schema::table('region_locales', function (Blueprint $table) {
            $table->unsignedInteger('region_id')->change();
        });

        Schema::table('region_locales', function (Blueprint $table) {
            $table->foreign('region_id')
                ->references('id')
                ->on('regions')
                ->onDelete('cascade');
        });


        Schema::table('brand_locales', function (Blueprint $table) {
            $table->unsignedInteger('brand_id')->change();
        });

        Schema::table('brand_locales', function (Blueprint $table) {
            $table->foreign('brand_id')
                ->references('id')
                ->on('brands')
                ->onDelete('cascade');
        });

        Schema::table('balance_histories', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
        });

        Schema::table('balance_histories', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('machinery_equipments', function (Blueprint $table) {
            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
                ->onDelete('cascade');
        });


        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
        });

        Schema::table('payments', function (Blueprint $table) {
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
        Schema::table('types', function (Blueprint $table) {
            //
        });
    }
}
