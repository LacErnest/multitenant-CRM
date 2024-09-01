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

class EarnOutLoanSheet implements
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
        $this->headings = ['Description', 'Amount', 'Already Paid', 'Issued at', 'Paid at'];

        return $this->headings;
    }

    public function styles(Worksheet $sheet): array
    {
        $conditional = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
        $conditional->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION);
        $conditional->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL);
        $conditional->addCondition('SEARCH("quarter",A1)>0');
        $conditional->getStyle()->getFont()->setBold(true);
        $conditionalStyles[] = $conditional;
        $sheet->getStyle('A')->setConditionalStyles($conditionalStyles);
        $sheet->getStyle('B')->setConditionalStyles($conditionalStyles);

        return [
          1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Deductibles';
    }

    public function columnFormats(): array
    {
        return [
          'B' => $this->euro ? NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE : NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
          'C' => $this->euro ? NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE : NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }

    public function array(): array
    {
        $summary = [];
        $customerArray = [];
        $emptyArray = [
          'Description' => '',
          'Amount' => '',
          'Already Paid' => '',
          'Issued at' => '',
          'Paid at' => '',
        ];

        if (isset($this->data['loans'])) {
            foreach ($this->data['loans'] as $dataItem) {
                $array['Description'] = $dataItem['description'];
                $array['Amount'] = $dataItem['amount'];
                $array['Already Paid'] = $dataItem['already_paid'];
                $array['Issued at'] = $dataItem['issued_at'] == '-' ? $dataItem['issued_at'] : date('Y-m-d', strtotime($dataItem['issued_at']));
                $array['Paid at'] = $dataItem['paid_at'] == '-' ? $dataItem['paid_at'] : date('Y-m-d', strtotime($dataItem['paid_at']));

                array_push($customerArray, $array);
            }
        }

        $loanArray = [
          'Open amount before quarter' => $this->data['open_loan_amount_before_quarter'] ?? 0,
          'Total amount owed' => $this->data['amount_of_loans_this_quarter'] ?? 0,
          'Total amount deducted from bonuses this quarter' => $this->data['amount_paid_this_quarter'] ?? 0,
          'Amount still to pay to Magic Media after this quarter' => $this->data['loan_amount_still_to_pay'] ?? 0,
        ];
        array_push($customerArray, $emptyArray);

        foreach ($loanArray as $key => $value) {
            $totalArray = [
              'Description' => $key,
              'Amount' => $value,
              'Already Paid' => '',
              'Issued at' => '',
              'Paid at' => '',
            ];
            array_push($customerArray, $totalArray);
        }
        array_push($summary, $customerArray, $emptyArray);

        return $summary;
    }
}
