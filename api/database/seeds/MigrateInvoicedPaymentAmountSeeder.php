<?php

use App\Services\InvoicePaymentService;
use Illuminate\Database\Seeder;

class MigrateInvoicedPaymentAmountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app(InvoicePaymentService::class)->migrateInvoicedPaymentsAmount();
    }
}
