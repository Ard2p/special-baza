<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDomainIdToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('domain_id')->nullable();
        });

        Schema::table('dispatcher_leads', function (Blueprint $table) {
            $table->unsignedInteger('domain_id')->nullable();
        });

        Schema::table('dispatcher_leads', function (Blueprint $table) {
            $table->foreign('domain_id')
                ->references('id')
                ->on('domains')
                ->onDelete('set null');
        });


        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('domain_id')
                ->references('id')
                ->on('domains')
                ->onDelete('set null');
        });

        $domain = \Modules\RestApi\Entities\Domain::whereAlias('ru')->first();
        if($domain) {
            \Modules\Orders\Entities\Order::query()->update([
                'domain_id' => $domain->id
            ]);

            \Modules\Dispatcher\Entities\Lead::query()->update([
                'domain_id' => $domain->id
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
