<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanySaleContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_sale_contracts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('url');
            $table->unsignedInteger('creator_id');


            $table->unsignedBigInteger('owner_id');
            $table->string('owner_type');

            $table->unsignedBigInteger('company_branch_id');
            $table->timestamps();
        });

        Schema::table('company_sale_contracts', function (Blueprint $table) {

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company\_sale_contracts');
    }
}
