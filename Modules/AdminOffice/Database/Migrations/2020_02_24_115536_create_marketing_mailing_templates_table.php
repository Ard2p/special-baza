<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarketingMailingTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::rename('templates', 'mailing_templates');

        Schema::table('mailing_templates', function (Blueprint $table) {
            $table->unsignedInteger('domain_id')->nullable();
            $table->boolean('can_delete')->default(1);
            $table->string('system_alias')->nullable();
        });

        Schema::table('mailing_templates', function (Blueprint $table) {
            $table->foreign('domain_id')
                ->references('id')
                ->on('domains')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mailing_templates');
    }
}
