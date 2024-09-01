<?php

namespace App\Exports\Sheets;

use App\Http\Resources\Export\OrderReportResource;
use App\Http\Resources\Export\PurchaseOrderReportResource;
use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OrderPurchaseOrdersSummarySheet implements FromView, ShouldAutoSize, WithEvents, WithTitle
{
    /**
     * @var Order
     */
    private $order;
    /**
     * @var array
     */
    private $purchaseOrders;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $purchaseOrders = $order->project->purchaseOrders()->with('resource')->get();
        $this->purchaseOrders = PurchaseOrderReportResource::collection($purchaseOrders)->resolve();
    }

    public function view(): View
    {
        $orderExportData = OrderReportResource::make($this->order)->resolve();
        $orderExportData = array_merge($orderExportData, [
          'purchase_orders' => $this->purchaseOrders,
        ]);
        return view('exports.order_purchase_orders_report_export', $orderExportData);
    }

    public function registerEvents(): array
    {
        return [
          AfterSheet::class => function (AfterSheet $event) {

              $event->sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
              $event->sheet->getStyle('A1:F' . (1 + count($this->purchaseOrders)))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
              $event->sheet->getStyle('A1:F' . (1 + count($this->purchaseOrders)))->getAlignment()->setHorizontal('center');
              $event->sheet->getStyle('B1:F' . (1 + count($this->purchaseOrders)))->getAlignment()->setHorizontal('right');
          },
        ];
    }

    public function title(): string
    {
        return 'Purchase orders';
    }
}
