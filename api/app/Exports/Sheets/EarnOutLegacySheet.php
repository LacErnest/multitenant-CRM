<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EarnOutLegacySheet implements
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
        $this->headings = ['Legacy Customer', 'Order', 'Legacy Revenue', 'Legacy Bonus'];

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

        return [
          1    => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Legacy Customers';
    }

    public function columnFormats(): array
    {
        return [
          'C' => $this->euro ? NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE : NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
          'D' => $this->euro ? NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE : NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }

    public function array(): array
    {
        $summary = [];
        $customerArray = [];
        $emptyArray = [
          'Legacy Customer' => '',
          'Order' => '',
          'Legacy Revenue' => '',
          'Legacy Bonus' => '',
        ];

        foreach ($this->data as $dataItem) {
            $count = 1;
            $array['Legacy Customer'] = $dataItem['name'];
            foreach ($dataItem['items'] as $item) {
                if ($count > 1) {
                    $array['Legacy Customer'] = '';
                }
                $array['Order'] = $item['name'];
                $array['Legacy Revenue'] = $item['legacy_revenue'];
                $array['Legacy Bonus'] = $item['legacy_bonus'];

                array_push($customerArray, $array);
                $count++;
            }

            $totalArray = [
              'Legacy Customer' => $count == 1 ? $dataItem['name'] : '',
              'Order' => 'Totals',
              'Legacy Revenue' => $count == 1 ? 0 : $dataItem['total_legacy_revenue'],
              'Legacy Bonus' => $count == 1 ? 0 : $dataItem['total_legacy_bonus'],
            ];
            array_push($customerArray, $totalArray, $emptyArray);
        }
        array_push($summary, $customerArray);

        return $summary;
    }
}
