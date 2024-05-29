<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignToCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('corp_brands', function (Blueprint $table) {
            $table->string('inn')->unique()->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('corp_companies', function (Blueprint $table) {
            $table->string('inn')->unique()->change();
            $table->unsignedBigInteger('corp_brand_id')->change();

        });

        Schema::table('corp_companies', function (Blueprint $table) {
            $table->foreign('corp_brand_id')
                ->references('id')
                ->on('corp_brands')
                ->onDelete('cascade');
        });

        Schema::table('employee_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('corp_company_id')->change();
        });
        Schema::table('employee_requests', function (Blueprint $table) {
            $table->foreign('corp_company_id')
                ->references('id')
                ->on('corp_companies')
                ->onDelete('cascade');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::dropIfExists('banks_companies');
        Schema::dropIfExists('banks_brands');

        Schema::create('corp_banks_relation', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('corp_bank_id');
            $table->string('bankable_id');
            $table->string('bankable_type');
        });

        Schema::table('corp_banks_relation', function (Blueprint $table) {
            $table->foreign('corp_bank_id')
                ->references('id')
                ->on('corp_banks')
                ->onDelete('cascade');
        });
        Schema::table('corp_banks', function (Blueprint $table) {
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
        Schema::table('', function (Blueprint $table) {

        });
    }
}
