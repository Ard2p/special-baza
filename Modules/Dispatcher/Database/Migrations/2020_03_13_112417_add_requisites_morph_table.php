<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRequisitesMorphTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dispatcher_customers', function (Blueprint $table) {
                $table->unsignedInteger('domain_id')->nullable();
        });

        Schema::table('dispatcher_customers', function (Blueprint $table) {
            $table->foreign('domain_id')
                ->references('id')
                ->on('domains')
                ->onDelete('set null');
        });

        Schema::table('dispatcher_contractors', function (Blueprint $table) {
            $table->unsignedInteger('domain_id')->nullable();
        });

        Schema::table('dispatcher_contractors', function (Blueprint $table) {
            $table->foreign('domain_id')
                ->references('id')
                ->on('domains')
                ->onDelete('set null');
        });

        $domain = \Modules\RestApi\Entities\Domain::query()->where('alias', 'trans-baza-ru')->first();

        foreach (\Modules\Dispatcher\Entities\Directories\Contractor::all() as $contractor) {
            $contractor->update(['domain_id' => $domain->id]);
        }

        foreach (\Modules\Dispatcher\Entities\Customer::all() as $customer) {
            $customer->update(['domain_id' => $domain->id]);
        }
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
