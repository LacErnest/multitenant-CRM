<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceModifiersSettingsToCompanySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->decimal('vat_default_value',4,2)->default(0);
            $table->decimal('vat_max_value',4,2)->default(20);
            $table->decimal('project_management_default_value',4,2)->default(10);
            $table->decimal('project_management_max_value',4,2)->default(50);
            $table->decimal('special_discount_default_value',4,2)->default(5);
            $table->decimal('special_discount_max_value',4,2)->default(20);
            $table->decimal('director_fee_default_value',4,2)->default(10);
            $table->decimal('director_fee_max_value',4,2)->default(50);
            $table->decimal('transaction_fee_default_value',4,2)->default(2);
            $table->decimal('transaction_fee_max_value',4,2)->default(10);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'vat_default_value',
                'vat_max_value',
                'project_management_default_value',
                'project_management_max_value',
                'special_discount_default_value',
                'special_discount_max_value',
                'director_fee_default_value',
                'director_fee_max_value',
                'transaction_fee_default_value',
                'transaction_fee_max_value'
            ]);
        });
    }
}
