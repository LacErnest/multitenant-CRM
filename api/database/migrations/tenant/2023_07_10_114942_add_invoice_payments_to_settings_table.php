<?php

use App\Models\InvoicePayment;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoicePaymentsToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('invoice_payment_number')->nullable()
                ->after('resource_invoice_number')
                ->default(0);
            $table->string('invoice_payment_number_format')->nullable()
                ->after('invoice_payment_number')
                ->default('P-[YYYY][MM]XXXX');
        });

        $paymentCount = InvoicePayment::count();
        
        foreach(Setting::all() as $setting){
            $setting->update([
                'invoice_payment_number_format'=>'P-YYYYMMXXXX',
                'invoice_payment_number'=>$paymentCount
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('invoice_payment_number', 'invoice_payment_number_format');
        });
    }
}
