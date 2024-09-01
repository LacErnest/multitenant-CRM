<?php

namespace App\Exports;


use App\Exports\Sheets\OrderEmployeesSummarySheet;
use App\Exports\Sheets\OrderPurchaseOrdersSummarySheet;
use App\Exports\Sheets\OrderSummarySheet;
use App\Models\Order;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OrderReportExport implements WithMultipleSheets
{
    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function sheets(): array
    {

        $sheets = [
          new OrderSummarySheet($this->order),
          new OrderPurchaseOrdersSummarySheet($this->order),
          new OrderEmployeesSummarySheet($this->order)
        ];

        return $sheets;
    }
}
