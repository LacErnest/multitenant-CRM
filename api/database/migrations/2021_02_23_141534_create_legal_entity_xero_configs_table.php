<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLegalEntityXeroConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('legal_entity_xero_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('xero_tenant_id')->nullable();
            $table->string('xero_access_token',1500)->nullable();
            $table->string('xero_refresh_token',255)->nullable();
            $table->string('xero_id_token', 2056)->nullable();
            $table->string('xero_oauth2_state', 2056)->nullable();
            $table->timestamp('xero_expires')->nullable();
            $table->timestamps();
        });

        Schema::table('legal_entities', function (Blueprint $table) {
            $table->foreign('legal_entity_xero_config_id')->references('id')->on('legal_entity_xero_configs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('legal_entities', function (Blueprint $table) {
            $table->dropForeign(['legal_entity_xero_config_id']);
        });

        Schema::dropIfExists('legal_entity_xero_configs');
    }
}
