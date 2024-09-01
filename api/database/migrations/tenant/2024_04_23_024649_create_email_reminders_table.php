<?php

use App\Enums\NotificationReminderType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->tinyInteger('type')->default(NotificationReminderType::due_in()->getIndex());
            $table->integer('value');
            $table->uuid('entity_id')->nullable();
            $table->string('entity_type')->nullable();
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
        Schema::dropIfExists('email_reminders');
    }
}
