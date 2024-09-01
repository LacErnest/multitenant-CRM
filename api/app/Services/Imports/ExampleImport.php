<?php

namespace App\Services\Imports;

use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithLimit;

class ExampleImport implements WithStartRow, WithLimit
{
    public function startRow(): int
    {
        return 2;
    }

    public function limit(): int
    {
        return 5;
    }
}
