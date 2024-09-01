<?php

namespace App\Services\Export\Xlsx;

use App\Http\Resources\Export\OrderResource;
use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OrderReportExport implements FromView, ShouldAutoSize, WithEvents
{
    /**
     * @var Order
     */
    private $order;
    /**
     * @var array
     */
    private $items;
    /**
     * @var array
     */
    private $purchaseOrders;
    /**
     * @var array
     */
    private $projectEmployees;

    public function __construct(Order $order, array $items = [], array $purchaseOrders = [], array $projectEmployees = [])
    {
        $this->order = $order;
        $this->items = $items;
        $this->purchaseOrders = $purchaseOrders;
        $this->projectEmployees = $projectEmployees;
    }

    public function view(): View
    {
        $orderExportData = OrderResource::make($this->order)->resolve();
        $orderExportData = array_merge($orderExportData, [
          'items' => $this->items,
          'purchase_orders' => $this->purchaseOrders,
          'project_employees' => $this->projectEmployees,
        ]);
        return view('exports.order_report_export', $orderExportData);
    }

    public function registerEvents(): array
    {
        return [
          AfterSheet::class => function (AfterSheet $event) {

              # Adding style for items table
              $event->sheet->getStyle('A10:L10')->getAlignment()->setHorizontal('center');
              $event->sheet->getStyle('A13:A' . (13 + count($this->items)))->getAlignment()->setWrapText(true);
              $event->sheet->getStyle('A12:L' . (12 + count($this->items)))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
              $event->sheet->getStyle('E12:' . 'G' . (12 + count($this->items)))->getAlignment()->setHorizontal('center');
              $event->sheet->getStyle('J12:' . 'J' . (12 + count($this->items)))->getAlignment()->setHorizontal('right');
              $event->sheet->getStyle('G' . (13 + count($this->items)) . ':G' . (13 + 7 + count($this->items)))->getAlignment()->setHorizontal('right');

              # Adding style fro purchase orders table
              $line = 13 + 7 + count($this->items) + 1;
              $event->sheet->getStyle('A'.($line + 1))->getAlignment()->setHorizontal('center');
              $event->sheet->getStyle('A'.($line + 3).':L' . ($line + 3 + count($this->purchaseOrders)))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
              $event->sheet->getStyle('A'.($line + 3).':L' . ($line + 3 + count($this->purchaseOrders)))->getAlignment()->setHorizontal('center');
              $event->sheet->getStyle('J'.($line + 3).':L' . ($line + 3 + count($this->purchaseOrders)))->getAlignment()->setHorizontal('right');
              $event->sheet->getStyle('G'.($line + 3 + count($this->purchaseOrders) +1).':L' . ($line + 3 + count($this->purchaseOrders) + 7))->getAlignment()->setHorizontal('right');

              # Adding style for project employees table
              $line += 3 + count($this->purchaseOrders) + 7 + 1;
              $event->sheet->getStyle('A'.($line + 1))->getAlignment()->setHorizontal('center');
              $event->sheet->getStyle('A'.($line + 3).':L' . ($line + 3 + count($this->projectEmployees)))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
              $event->sheet->getStyle('A'.($line + 3).':L' . ($line + 3 + count($this->projectEmployees)))->getAlignment()->setHorizontal('center');
              $event->sheet->getStyle('J'.($line + 3).':L' . ($line + 3 + count($this->projectEmployees)))->getAlignment()->setHorizontal('right');
              $event->sheet->getStyle('G' . ($line + 3 + count($this->projectEmployees) + 2))->getAlignment()->setHorizontal('right');
          },
        ];
    }
}
