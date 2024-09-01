<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EarnOutCustomerSheet implements
    FromArray,
    WithHeadings,
    WithStrictNullComparison,
    ShouldAutoSize,
    WithStyles,
    WithTitle,
    WithColumnFormatting
{
    private array $data;
    private bool $euro;

    public function __construct(array $data, bool $euro)
    {
        $this->data = $data;
        $this->euro = $euro;
    }

    public function headings(): array
    {
        $this->headings = ['Customer', 'Order', 'Revenue', 'PO Costs', 'External Employee Costs'];

        return $this->headings;
    }

    public function styles(Worksheet $sheet): array
    {
        $conditional = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $conditional->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION);
        $conditional->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL);
        $conditional->addCondition('SEARCH("Totals",B1)>0');
        $conditional->getStyle()->getFont()->setBold(true);
        $conditionalStyles[] = $conditional;
        $sheet->getStyle('B')->setConditionalStyles($conditionalStyles);
        $sheet->getStyle('C')->setConditionalStyles($conditionalStyles);
        $sheet->getStyle('D')->setConditionalStyles($conditionalStyles);
        $sheet->getStyle('E')->setConditionalStyles($conditionalStyles);

        return [
          1    => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Customers';
    }

    public function columnFormats(): array
    {
        return [
          'C' => $this->euro ? NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE : NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
          'D' => $this->euro ? NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE : NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
          'E' => $this->euro ? NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE : NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }

    public function array(): array
    {
        $summary = [];
        $customerArray = [];
        $emptyArray = [
          'Customer' => '',
          'Order' => '',
          'Revenue' => '',
          'PO Costs' => '',
          'External Employee Costs' => '',
        ];

        foreach ($this->data as $dataItem) {
            $count = 1;
            foreach ($dataItem['items'] as $item) {
                $array = [];
                if ($count == 1) {
                    $array['Customer'] = $dataItem['name'];
                } else {
                    $array['Customer'] = '';
                }
                $array['Order'] = $item['name'];
                $array['Revenue'] = $item['revenue'];
                $array['PO Costs'] = $item['costs'];
                $array['External Employee Costs'] = $item['external_employee_costs'];

                array_push($customerArray, $array);
                $count++;
            }

            $totalArray = [
              'Customer' => '',
              'Order' => 'Totals',
              'Revenue' => $dataItem['total_revenue'],
              'PO Costs' => $dataItem['total_costs'],
              'External Employee Costs' => $dataItem['total_external_employee_costs'],
            ];
            array_push($customerArray, $totalArray, $emptyArray);
        }
        array_push($summary, $customerArray);

        return $summary;
    }
}
