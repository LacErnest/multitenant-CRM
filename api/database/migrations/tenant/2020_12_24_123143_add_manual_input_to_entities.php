<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddManualInputToEntities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->boolean('manual_input')->default(0)->after('status');
            $table->decimal('manual_price', 15, 6)->default(0)->after('total_vat_usd');
            $table->decimal('manual_vat', 15, 6)->default(0)->after('manual_price');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('manual_input')->default(0)->after('status');
            $table->decimal('manual_price', 15, 6)->default(0)->after('total_vat_usd');
            $table->decimal('manual_vat', 15, 6)->default(0)->after('manual_price');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('manual_input')->default(0)->after('status');
            $table->decimal('manual_price', 15, 6)->default(0)->after('total_vat_usd');
            $table->decimal('manual_vat', 15, 6)->default(0)->after('manual_price');
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->boolean('manual_input')->default(0)->after('status');
            $table->decimal('manual_price', 15, 6)->default(0)->after('total_vat_usd');
            $table->decimal('manual_vat', 15, 6)->default(0)->after('manual_price');
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
            $table->dropColumn('manual_input', 'manual_price', 'manual_vat');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('manual_input', 'manual_price', 'manual_vat');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('manual_input', 'manual_price', 'manual_vat');
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('manual_input', 'manual_price', 'manual_vat');
        });
    }
}
