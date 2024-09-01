<?php

namespace App\Exports;

use App\Exports\Sheets\EarnOutBonusSheet;
use App\Exports\Sheets\EarnOutCustomerSheet;
use App\Exports\Sheets\EarnOutGeneralPurchaseOrdersSheet;
use App\Exports\Sheets\EarnOutLegacySheet;
use App\Exports\Sheets\EarnOutLoanSheet;
use App\Exports\Sheets\EarnOutMonthlyCostSheet;
use App\Exports\Sheets\EarnOutSalarySheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EarnOutSummaryExport implements WithMultipleSheets
{
    protected array $data;
    protected array $headings;
    protected bool $euro;

    public function __construct(array $data, bool $euro)
    {
        $this->data = $data;
        $this->euro = $euro;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new EarnOutCustomerSheet($this->data['orders_per_customer'], $this->euro);
        $sheets[] = new EarnOutGeneralPurchaseOrdersSheet($this->data['purchase_orders_without_orders'], $this->euro);
        $sheets[] = new EarnOutLegacySheet($this->data['orders_per_legacy_customer'], $this->euro);
        $sheets[] = new EarnOutSalarySheet($this->data['salaries']['items'], $this->data['salaries']['total_internal_salary'], $this->euro);
        $sheets[] = new EarnOutMonthlyCostSheet($this->data['rents']['items'], $this->data['rents']['total_rent_costs'], $this->euro);
        $sheets[] = new EarnOutLoanSheet($this->data['loans'], $this->euro);
        $sheets[] = new EarnOutBonusSheet($this->data, $this->euro);

        return $sheets;
    }
}
