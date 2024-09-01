<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\TablePreferenceType;
use App\Services\TablePreferenceService;

class DropInvoicePaymentsTablePreference extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * @var \App\Services\TablePreferenceService
         */
        $tablepreferenceService = app(TablePreferenceService::class);
        $tablepreferenceService->deleteToTablePreferences(TablePreferenceType::invoice_payments()->getIndex());
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
