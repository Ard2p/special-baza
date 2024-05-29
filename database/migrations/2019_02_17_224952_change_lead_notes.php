<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeLeadNotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lead_notes', function (Blueprint $table) {
            $table->unsignedInteger('lead_id');
            $table->unsignedInteger('manager_id');
            $table->timestamp('date')->nullable();
        });
        Schema::table('lead_notifications', function (Blueprint $table) {
            $table->text('note')->nullable();
            $table->text('attachments')->nullable();
            $table->string('note_type')->nullable();
            $table->boolean('is_sent')->default(0);
            $table->boolean('read_from_modal')->default(0);
            $table->boolean('is_read')->default(0);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lead_notes', function (Blueprint $table) {
            $table->dropColumn('lead_id');
            $table->dropColumn('manager_id');
        });
    }
}
