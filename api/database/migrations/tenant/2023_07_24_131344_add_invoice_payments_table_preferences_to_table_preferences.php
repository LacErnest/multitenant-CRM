<?php

use App\Enums\TablePreferenceType;
use App\Services\TablePreferenceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoicePaymentsTablePreferencesToTablePreferences extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tablePreferenceService = app(TablePreferenceService::class);

        $tablePreferenceService->addDetailsToTablePreferences(TablePreferenceType::invoice_payments()->getIndex());
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
