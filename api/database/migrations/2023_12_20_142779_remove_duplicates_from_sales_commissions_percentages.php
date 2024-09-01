<?php

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\SalesCommissionPercentage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tenancy\Facades\Tenancy;

class RemoveDuplicatesFromSalesCommissionsPercentages extends Migration
{

    private function removeDuplicatedInvoices()
    {
        $duplicatesInvoice = SalesCommissionPercentage::select('invoice_id', 'order_id', 'sales_person_id')
            ->groupBy('order_id', 'sales_person_id', 'invoice_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicatesInvoice as $duplicate) {
            SalesCommissionPercentage::where('invoice_id', $duplicate->invoice_id)
                ->where('order_id', $duplicate->order_id)
                ->where('sales_person_id', $duplicate->sales_person_id)
                ->orderByDesc('type')
                ->chunk(2, function ($chunk) {
                    foreach ($chunk->skip(1) as $skip) {
                        $skip->delete();
                    }
                });
        }
    }

    private function removeDuplicatesCommissionsForSameSalesPerson()
    {
        $duplicates = SalesCommissionPercentage::select('order_id', 'sales_person_id')
            ->groupBy('order_id', 'sales_person_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        
        
        foreach ($duplicates as $duplicate) {
            foreach (Company::all() as $company) {
                Tenancy::setTenant($company);
                if ($order = Order::find($duplicate->order_id)) {
                    $invoices = $order->invoices()
                        ->where('shadow', false)
                        ->pluck('id');
                    if (!empty($invoices)) {
                        SalesCommissionPercentage::whereNotIn('invoice_id', $invoices)
                            ->where('order_id', $duplicate->order_id)
                            ->where('sales_person_id', $duplicate->sales_person_id)
                            ->delete();
                    }
                }
            }
        }
        Tenancy::setTenant(null);
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('sales_commission_percentages', function (Blueprint $table) {
            $table->dropUniqueIfExists('unique_sales_commission_percentages_composite_key');
        });

        try {

            DB::beginTransaction();

            $this->removeDuplicatedInvoices();

            $this->removeDuplicatesCommissionsForSameSalesPerson();

            // Adding unique index
            Schema::table('sales_commission_percentages', function (Blueprint $table) {
                $table->unique(['invoice_id', 'order_id', 'sales_person_id'], 'unique_sales_commission_percentages_composite_key');
            });

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_commission_percentages', function (Blueprint $table) {
            $table->dropUniqueIfExists('unique_sales_commission_percentages_composite_key');
        });
    }
}
