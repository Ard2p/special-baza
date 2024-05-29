<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDispatcherLead extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (\App\User\EntityRequisite::query()->where('requisite_type', '=', '')
                     ->orWhereNull('requisite_type')->get() as $requisite) {
            $requisite->requisite_type = \App\User::class;
            $requisite->requisite_id = $requisite->user_id;
            $requisite->save();
        }

        foreach (\App\User\IndividualRequisite::query()->where('requisite_type', '=', '')
                     ->orWhereNull('requisite_type')->get() as $requisite) {
            $requisite->requisite_type = \App\User::class;
            $requisite->requisite_id = $requisite->user_id;
            $requisite->save();
        }
        Schema::table('dispatcher_leads_contractors', function (Blueprint $table) {
            $table->unsignedBigInteger('sum')->default(0);
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
