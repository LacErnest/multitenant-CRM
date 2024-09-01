<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVatEnumAndPercentageToEntitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedTinyInteger('vat_status')->default(1)->after('date');
            $table->float('vat_percentage')->nullable()->after('vat_status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('vat_status')->default(1)->after('date');
            $table->float('vat_percentage')->nullable()->after('vat_status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedTinyInteger('vat_status')->default(1)->after('date');
            $table->float('vat_percentage')->nullable()->after('vat_status');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('vat_status')->default(1)->after('date');
            $table->float('vat_percentage')->nullable()->after('vat_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('vat_status', 'vat_percentage');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('vat_status', 'vat_percentage');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('vat_status', 'vat_percentage');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('vat_status', 'vat_percentage');
        });
    }
}
