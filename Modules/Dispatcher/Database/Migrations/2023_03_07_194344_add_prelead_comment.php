<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\PreLead;

class AddPreleadComment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new Lead())->getTable(), function (Blueprint $table) {
            $table->boolean('accepted')->default(false);
            $table->timestamp('first_date_rent')->nullable();
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
