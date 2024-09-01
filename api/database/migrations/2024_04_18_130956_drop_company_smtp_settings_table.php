<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropCompanySmtpSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('company_smtp_settings');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('company_smtp_settings', function (Blueprint $table) {
            $table->uuid('company_id')->primary();
            $table->boolean('enable_smtp_configuration')->default(false);
            $table->string('smtp_host')->nullable()->default(null);
            $table->string('smtp_port')->nullable()->default(null);
            $table->string('smtp_encryption')->nullable()->default(null);
            $table->string('smtp_username')->nullable()->default(null);
            $table->string('smtp_password', 255)->nullable()->default(null);
            $table->string('from_email')->nullable()->default(null);
            $table->string('from_name')->nullable()->default(null);

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }
}
