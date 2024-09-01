<?php

namespace App\Exports\Sheets;

use App\Http\Resources\Export\ItemReportResource;
use App\Http\Resources\Export\OrderReportResource;
use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OrderSummarySheet implements FromView, ShouldAutoSize, WithEvents, WithTitle
{
    /**
     * @var Order
     */
    private $order;
    /**
     * @var array
     */
    private $items;

    public function __construct(Order $order)
    {
        $this->order = $order;

        //Add items of order to template
        $orderItems = $order->items()->with('priceModifiers')->orderBy('order')->get();
        $items = ItemReportResource::collection($orderItems)->resolve();

        $items = array_map(function ($item) {
            $item['VAR_I_M_DESCRIPTION'] = '';
            $item['VAR_I_M_QUANTITY'] = '';
            if (!empty($item['VAR_price_modifier'])) {
                foreach ($item['VAR_price_modifier'] as $modifier) {
                    $item['VAR_I_M_DESCRIPTION'] .= $modifier['VAR_I_M_DESCRIPTION'] . "\n";
                    $item['VAR_I_M_QUANTITY'] .= $modifier['VAR_I_M_QUANTITY'] . "\n";
                }
            }
            unset($item['VAR_price_modifier']);

            return $item;
        }, $items);

        $this->items = $items;
    }

    public function view(): View
    {
        $orderExportData = OrderReportResource::make($this->order)->resolve();
        $orderExportData = array_merge($orderExportData, [
          'items' => $this->items
        ]);
        return view('exports.order_report_export', $orderExportData);
    }

    public function registerEvents(): array
    {
        return [
          AfterSheet::class => function (AfterSheet $event) {
              # Adding style for items table
              $event->sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
              $event->sheet->getStyle('B3:L3')->getAlignment()->setHorizontal('center');
              $event->sheet->getStyle('A4:A' . (4 + count($this->items)))->getAlignment()->setWrapText(true);
              $event->sheet->getStyle('A3:L' . (3 + count($this->items)))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
              $event->sheet->getStyle('B3:' . 'G' . (3 + count($this->items)))->getAlignment()->setHorizontal('center');
              $event->sheet->getStyle('J3:' . 'J' . (3 + count($this->items)))->getAlignment()->setHorizontal('right');
              $event->sheet->getStyle('J' . (4 + count($this->items)) . ':J' . (4 + 13 + count($this->items)))->getAlignment()->setHorizontal('right');
              $event->sheet->getDelegate()->removeRow(2);
          },
        ];
    }

    public function title(): string
    {
        return 'Order';
    }
}
