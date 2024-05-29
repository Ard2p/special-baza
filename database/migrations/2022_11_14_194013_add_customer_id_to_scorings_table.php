<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerIdToScoringsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scorings', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->constrained('dispatcher_customers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scorings', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
    }
}
