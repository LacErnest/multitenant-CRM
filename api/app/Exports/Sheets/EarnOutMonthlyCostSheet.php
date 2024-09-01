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

class EarnOutMonthlyCostSheet implements
    FromArray,
    WithHeadings,
    WithStrictNullComparison,
    ShouldAutoSize,
    WithStyles,
    WithTitle,
    WithColumnFormatting
{
    private array $data;
    private float $total;
    private bool $euro;

    public function __construct(array $data, float $total, bool $euro)
    {
        $this->data = $data;
        $this->total = $total;
        $this->euro = $euro;
    }

    public function headings(): array
    {
        $this->headings = ['Name', 'Start of cost', 'End of cost', 'Cost per month', 'Cost for this quarter'];

        return $this->headings;
    }

    public function styles(Worksheet $sheet): array
    {
        $conditional = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $conditional->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION);
        $conditional->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL);
        $conditional->addCondition('SEARCH("Total monthly costs",A1)>0');
        $conditional->getStyle()->getFont()->setBold(true);
        $conditionalStyles[] = $conditional;
        $sheet->getStyle('A')->setConditionalStyles($conditionalStyles);
        $sheet->getStyle('E')->setConditionalStyles($conditionalStyles);

        return [
          1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Monthly Costs';
    }

    public function columnFormats(): array
    {
        return [
          'D' => $this->euro ? NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE : NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
          'E' => $this->euro ? NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE : NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }

    public function array(): array
    {
        $summary = [];
        $customerArray = [];
        $emptyArray = [
          'Name' => '',
          'Start of cost' => '',
          'End of cost' => '',
          'Cost per month' => '',
          'Cost for this quarter' => '',
        ];

        foreach ($this->data as $dataItem) {
            $array['Name'] = $dataItem['name'];
            $array['Start of cost'] = $dataItem['start_of_rent'];
            $array['End of cost'] = $dataItem['end_of_rent'];
            $array['Cost per month'] = $dataItem['cost_per_month'];
            $array['Cost for this quarter'] = $dataItem['cost_for_this_quarter'];

            array_push($customerArray, $array);
        }

        $totalArray = [
          'Name' => 'Total monthly costs',
          'Start of cost' => '',
          'End of cost' => '',
          'Cost per month' => '',
          'Cost for this quarter' => $this->total,
        ];
        array_push($customerArray, $emptyArray, $totalArray);
        array_push($summary, $customerArray);

        return $summary;
    }
}
