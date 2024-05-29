<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAmoCrmOwner extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::table('integrations_amo_auth_tokens')->delete();
        Schema::table('integrations_amo_auth_tokens', function (Blueprint $table){
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('company_branch_id');
        });

        Schema::table('integrations_amo_auth_tokens', function (Blueprint $table) {
            $table->dropColumn('user_id');
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
        //
    }
}
