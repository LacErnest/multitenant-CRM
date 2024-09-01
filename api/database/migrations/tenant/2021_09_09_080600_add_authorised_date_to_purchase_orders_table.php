<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuthorisedDateToPurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->date('authorised_date')->after('date')->nullable();
        });

        $this->setAuthorisedDate();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('authorised_date');
        });
    }

    private function setAuthorisedDate()
    {
        $purchaseOrders = PurchaseOrder::all();
        foreach ($purchaseOrders as $purchaseOrder) {
            if (PurchaseOrderStatus::isAuthorised($purchaseOrder->status) || PurchaseOrderStatus::isBilled($purchaseOrder->status) ||
                PurchaseOrderStatus::isPaid($purchaseOrder->status)) {
                $purchaseOrder->authorised_date = $purchaseOrder->date;
                $purchaseOrder->save();
            }
        }
    }
}
