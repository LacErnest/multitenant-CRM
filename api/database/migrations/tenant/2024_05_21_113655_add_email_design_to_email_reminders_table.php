<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailDesignToEmailRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_reminders', function (Blueprint $table) {
            $table->uuid('design_template_id')->nullable();
            $table->foreign('design_template_id')->references('id')->on('design_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('email_reminders', function (Blueprint $table) {
            $table->dropForeign('design_template_id');
            $table->dropColumn('design_template_id');
        });
    }
}
