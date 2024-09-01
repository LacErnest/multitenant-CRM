<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFromNameToCompanyNotificationSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_notification_settings', function (Blueprint $table) {
            $table->string('from_name')->nullable()
            ->description('Name from which notification will be sent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_notification_settings', function (Blueprint $table) {
            $table->dropColumn('from_name');
        });
    }
}
