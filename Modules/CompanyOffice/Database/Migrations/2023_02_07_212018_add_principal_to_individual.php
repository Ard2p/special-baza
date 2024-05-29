<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrincipalToIndividual extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create((new \App\User\PrincipalDoc())->getTable(), function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_rent')->default(false);
            $table->boolean('is_service')->default(false);
            $table->boolean('is_part_sale')->default(false);
            $table->jsonb('scans')->nullable();
            $table->unsignedInteger('individual_requisite_id');
        });

        Schema::table((new \App\User\PrincipalDoc())->getTable(), function (Blueprint $table) {
            $table->foreign('individual_requisite_id')
                ->references('id')
                ->on((new \App\User\IndividualRequisite())->getTable())
                ->cascadeOnDelete();
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
