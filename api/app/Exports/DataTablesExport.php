<?php

namespace App\Exports;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataTablesExport implements FromArray, WithStrictNullComparison, WithHeadings, ShouldAutoSize, WithStyles
{
    protected array $entities;
    protected array $columns;
    protected array $headings;

    public function __construct(array $entities, array $columns)
    {
        $this->entities = $entities;
        $this->columns = $columns;

        $this->columns = array_filter(array_map(function ($item) {
            if ($item->prop != 'details') {
                if (Str::endsWith($item->prop, '_id')) {
                    $item->prop = rtrim($item->prop, '_id');
                }

                return $item;
            }
        }, $this->columns));
    }

    public function styles(Worksheet $sheet)
    {
        return [
          1    => ['font' => ['bold' => true]],
        ];
    }

    public function headings(): array
    {
        $this->headings = array_map(function ($item) {
              return $item->name;
        }, $this->columns);

        return $this->headings;
    }

    public function array(): array
    {
        static $instance = 'App\Enums\\';
        $entityArray = [];

        foreach ($this->entities as $entity) {
            $array = [];
            foreach ($this->columns as $column) {
                if ($column->type == 'enum') {
                    $enum = $this->convertEnum(Str::studly($column->enum));
                    $enum = $instance . $enum;
                    $array[$column->prop] = !$this->isPropValueValid($column->prop, $entity) ? '' : $enum::make($entity[$column->prop])->getValue();
                } elseif ($column->type == 'date') {
                    $array[$column->prop] = !$this->isPropValueValid($column->prop, $entity) ? '' : $entity[$column->prop]->format('Y-m-d');
                } else {
                    $array[$column->prop] = $entity[$column->prop] ?? '';
                }
            }
            array_push($entityArray, $array);
        }

        return $entityArray;
    }

    private function convertEnum($enumType): string
    {
        $enum = $enumType;
        if ($enumType == 'Purchaseorderstatus') {
            $enum = 'PurchaseOrderStatus';
        } elseif ($enumType == 'Contactgendertypes') {
            $enum = 'ContactGenderTypes';
        } elseif (Str::endsWith($enumType, 'status')) {
            $enum = Str::replaceLast('status', '', $enumType);
            $enum = $enum . 'Status';
        } elseif (Str::endsWith($enumType, 'type')) {
            $enum = Str::replaceLast('type', '', $enumType);
            $enum = $enum . 'Type';
        } elseif (Str::endsWith($enumType, 'code')) {
            $enum = Str::replaceLast('code', '', $enumType);
            $enum = $enum . 'Code';
        } elseif (Str::endsWith($enumType, 'role')) {
            $enum = Str::replaceLast('role', '', $enumType);
            $enum = $enum . 'role';
        }

        return $enum;
    }

    private function isPropValueValid($prop, $entity): bool
    {
        return isset($entity[$prop]) && !($entity[$prop] === null);
    }
}
