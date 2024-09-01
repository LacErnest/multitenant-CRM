<?php

namespace App\Exports\Sheets;

use App\Http\Resources\Export\OrderReportResource;
use App\Http\Resources\Export\ProjectEmployeeReportResource;
use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OrderEmployeesSummarySheet implements FromView, ShouldAutoSize, WithEvents, WithTitle
{
    /**
     * @var Order
     */
    private $order;
    /**
     * @var array
     */
    private $projectEmployees;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $projectEmployees = $order->project->employees()->get();
        $projectEmployees = ProjectEmployeeReportResource::collection($projectEmployees)->resolve();
        $this->projectEmployees = $projectEmployees;
    }

    public function view(): View
    {
        $orderExportData = OrderReportResource::make($this->order)->resolve();
        $orderExportData = array_merge($orderExportData, [
          'project_employees' => $this->projectEmployees,
        ]);
        return view('exports.order_employees_report_export', $orderExportData);
    }

    public function registerEvents(): array
    {
        return [
          AfterSheet::class => function (AfterSheet $event) {
              $event->sheet->getStyle('A1:I' . (1 + count($this->projectEmployees)))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
              $event->sheet->getStyle('A1:I' . (1 + count($this->projectEmployees)))->getAlignment()->setHorizontal('center');
              $event->sheet->getStyle('J1:I' . (1 + count($this->projectEmployees)))->getAlignment()->setHorizontal('right');
              $event->sheet->getStyle('B' . (1 + count($this->projectEmployees) + 2))->getAlignment()->setHorizontal('right');
              $event->sheet->getStyle('F' . (1 + count($this->projectEmployees) + 2))->getAlignment()->setHorizontal('right');
          },
        ];
    }

    public function title(): string
    {
        return 'Employees';
    }
}
