<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddManagerPhoneToCallHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('telephony_call_histories', function (Blueprint $table) {

            $table->string('manager_phone')->nullable();
        });

        foreach (\Modules\Integrations\Entities\Telpehony\TelephonyCallHistory::all() as $item) {
            $item->update([
                'manager_phone' => $item->raw_data->diversion ?? null
            ]);
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
