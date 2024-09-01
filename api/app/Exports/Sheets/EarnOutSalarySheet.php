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

class EarnOutSalarySheet implements
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
        $this->headings = ['Employee Name', 'Start of employment', 'End of employment', 'Salary per month', 'Salary for this quarter'];

        return $this->headings;
    }

    public function styles(Worksheet $sheet): array
    {
        $conditional = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $conditional->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION);
        $conditional->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL);
        $conditional->addCondition('SEARCH("Total salary",A1)>0');
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
        return 'Salaries';
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
          'Employee Name' => '',
          'Start of employment' => '',
          'End of employment' => '',
          'Salary per month' => '',
          'Salary for this quarter' => '',
        ];

        foreach ($this->data as $dataItem) {
            $array['Employee Name'] = $dataItem['name'];
            $array['Start of employment'] = $dataItem['start_of_employment'];
            $array['End of employment'] = $dataItem['end_of_employment'];
            $array['Salary per month'] = $dataItem['salary_per_month'];
            $array['Salary for this quarter'] = $dataItem['salary_for_this_quarter'];

            array_push($customerArray, $array);
        }

        $totalArray = [
          'Employee Name' => 'Total salary',
          'Start of employment' => '',
          'End of employment' => '',
          'Salary per month' => '',
          'Salary for this quarter' => $this->total,
        ];
        array_push($customerArray, $emptyArray, $totalArray);
        array_push($summary, $customerArray);

        return $summary;
    }
}
