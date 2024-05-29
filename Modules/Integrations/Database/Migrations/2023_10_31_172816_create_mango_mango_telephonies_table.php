<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMangoMangoTelephoniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telephony_mango', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('token');
            $table->string('sign');
            $table->jsonb('settings')->nullable();
            $table->unsignedBigInteger('company_branch_id');
            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');
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
        Schema::dropIfExists('mango_mango_telephonies');
    }
}
