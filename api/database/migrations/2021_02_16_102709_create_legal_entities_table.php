<?php

use App\Models\LegalEntity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CreateLegalEntitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('legal_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('vat_number')->nullable();
            $table->uuid('address_id')->nullable();
            $table->string('swift')->nullable();
            $table->string('bic')->nullable();
            $table->uuid('european_bank_id')->nullable();
            $table->uuid('american_bank_id')->nullable();
            $table->uuid('legal_entity_xero_config_id')->nullable();
            $table->uuid('legal_entity_setting_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('address_id')->references('id')->on('customer_addresses')->onDelete('set null');
        });

        $this->deleteAndCreateLegalEntitiesIndex();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('legal_entities');
    }

    private function deleteAndCreateLegalEntitiesIndex()
    {
        try {
            LegalEntity::deleteIndex();
        } catch (Exception $e) {
            Log::error('Could not deleted index of legal entities. Reason: ' . $e->getMessage());
            Log::error($e);
        }
        LegalEntity::createIndex();
    }
}
