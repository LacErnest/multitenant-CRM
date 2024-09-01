<?php

use App\Enums\ContactGenderTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateXeroEntityStorageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xero_entity_storages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('xero_id');
            $table->uuid('legal_entity_id');
            $table->uuid('document_id');
            $table->string('document_type');
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
        Schema::dropIfExists('xero_entity_storages');
    }
}
