<?php

use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AlterTableServicesAddResourceId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->uuid('resource_id')->nullable()->after('price_unit');
            $table->foreign('resource_id')->references('id')->on('resources')->onDelete('set null');
        });

        $this->deleteAndCreateServiceIndex();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('resource_id');
        });
    }

    private function deleteAndCreateServiceIndex()
    {
        try {
            Service::deleteIndex();
        } catch (Exception $e) {
            Log::error('Could not deleted index of services. Reason: ' . $e->getMessage());
        }
        Service::createIndex();
        Service::addAllToIndexWithoutScopes();
    }
}
