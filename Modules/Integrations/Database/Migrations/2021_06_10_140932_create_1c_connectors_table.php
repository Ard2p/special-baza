<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Create1cConnectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('1c_connectors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('onec_id');
            $table->unsignedBigInteger('company_branch_id');
            $table->timestamps();
        });

        Schema::table('1c_connectors', function (Blueprint $table) {

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
        Schema::dropIfExists('1c\_connectors');
    }
}
