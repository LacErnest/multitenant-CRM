<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLegalEntityNotificationSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('legal_entity_notification_settings', function (Blueprint $table) {

            $table->uuid('legal_entity_id')->primary();
            $table->boolean('enable_submited_invoice_notification')->default(false);
            $table->text('notification_footer')->nullable();
            $table->json('notification_contacts')->nullable();

            $table->foreign('legal_entity_id')
                ->references('id')
                ->on('legal_entities')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('legal_entity_notification_settings');
    }
}
