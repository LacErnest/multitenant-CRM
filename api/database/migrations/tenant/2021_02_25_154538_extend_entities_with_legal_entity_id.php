<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendEntitiesWithLegalEntityId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->uuid('legal_entity_id')->nullable()->after('id');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('legal_entity_id')->nullable()->after('id');
            $table->dropUnique('invoices_number_unique');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('legal_entity_id')->nullable()->after('id');
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->uuid('legal_entity_id')->nullable()->after('id');
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->uuid('legal_entity_id')->nullable()->after('id');
        });
        Schema::table('resources', function (Blueprint $table) {
            $table->uuid('legal_entity_id')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('legal_entity_id');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('legal_entity_id');
            $table->unique('number');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('legal_entity_id');
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('legal_entity_id');
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('legal_entity_id');
        });
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn('legal_entity_id');
        });
    }
}
