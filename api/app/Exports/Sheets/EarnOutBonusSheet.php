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

class EarnOutBonusSheet implements
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
        $this->headings = ['Gross margin bonus calculation', 'Amount', '        ', 'Earn out bonus calculation', 'Amount'];

        return $this->headings;
    }

    public function styles(Worksheet $sheet): array
    {
        $conditional = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $conditional->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION);
        $conditional->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL);
        $conditional->addCondition('SEARCH("bonus",A1)>0');
        $conditional->getStyle()->getFont()->setBold(true);
        $conditionalStyles[] = $conditional;
        $conditional = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $conditional->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION);
        $conditional->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL);
        $conditional->addCondition('SEARCH("amount",A1)>0');
        $conditional->getStyle()->getFont()->setBold(true);
        $conditionalStyles[] = $conditional;
        $sheet->getStyle('A')->setConditionalStyles($conditionalStyles);
        $sheet->getStyle('B')->setConditionalStyles($conditionalStyles);

        $conditional_2 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $conditional_2->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION);
        $conditional_2->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL);
        $conditional_2->addCondition('SEARCH("bonus",D1)>0');
        $conditional_2->getStyle()->getFont()->setBold(true);
        $conditionalStyles_2[] = $conditional_2;

        $sheet->getStyle('D')->setConditionalStyles($conditionalStyles_2);
        $sheet->getStyle('E')->setConditionalStyles($conditionalStyles_2);

        return [
          1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Bonuses';
    }

    public function columnFormats(): array
    {
        return [
          'B' => $this->euro ? NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE : NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
          'E' => $this->euro ? NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE : NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }

    public function array(): array
    {
        $summary = [];
        $customerArray = [];
        $emptyArray = [
          'Gross margin bonus calculation' => '',
          'Amount' => '',
          'empty' => '',
          'Earn out bonus calculation' => '',
          'Amount_2' => '',
        ];

        $grossMarginArray = [
          'Total revenue' => $this->data['total_revenue'],
          'Total PO costs' => '-' . $this->data['total_costs'],
          'Total external employee costs' => '-' . $this->data['total_external_employee_costs'],
          'Total monthly costs' => '-' . $this->data['rents']['total_rent_costs'],
          'Total salary costs' => '-' . $this->data['salaries']['total_internal_salary'],
          'Gross margin (' . $this->data['gross_margin_ratio'] . '%)' => $this->data['gross_margin'],
          'Gross margin bonus (' . $this->data['gross_margin_percentage'] . '%)' => $this->data['gross_margin_bonus'],
        ];
        $count = 0;

        foreach ($grossMarginArray as $key => $value) {
            $totalArray = [
              'Gross margin bonus calculation' => $key,
              'Amount' => $value,
              'empty' => '',
              'Earn out bonus calculation' => $count == 0 ? 'Total legacy revenue' : ($count == 1 ? 'Earn out bonus (' . $this->data['earnout_percentage'] . '%)' : ''),
              'Amount_2' => $count == 0 ? $this->data['total_legacy_amount'] : ($count == 1 ? $this->data['earnout_bonus'] : ''),
            ];
            $count++;
            array_push($customerArray, $totalArray);
        }
        array_push($summary, $emptyArray, $customerArray, $emptyArray);

        $customerArray = [
          'Gross margin bonus calculation' => 'Total bonus',
          'Amount' => $this->data['total_bonus'],
          'empty' => '',
          'Earn out bonus calculation' => '',
          'Amount_2' => '',
        ];
        array_push($summary, $emptyArray, $customerArray);

        $customerArray = [
          'Gross margin bonus calculation' => 'Total deductible',
          'Amount' => $this->data['loans']['amount_paid_this_quarter'] ?? 0,
          'empty' => '',
          'Earn out bonus calculation' => '',
          'Amount_2' => '',
        ];
        array_push($summary, $emptyArray, $customerArray);

        $customerArray = [
          'Gross margin bonus calculation' => 'Total amount to be paid',
          'Amount' => $this->data['total_bonus_paid'],
          'empty' => '',
          'Earn out bonus calculation' => '',
          'Amount_2' => '',
        ];
        array_push($summary, $emptyArray, $customerArray);

        return $summary;
    }
}
