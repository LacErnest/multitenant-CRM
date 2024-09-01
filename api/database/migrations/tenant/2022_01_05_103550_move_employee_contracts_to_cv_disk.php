<?php

use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MoveEmployeeContractsToCvDisk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $employees = Employee::all();

        foreach ($employees as $employee) {
            $contract = $employee->getFirstMedia('contract');
            if ($contract) {
                $employee->addMedia($contract->getPath())
                    ->preservingOriginal()
                    ->usingFileName($contract->file_name)->toMediaCollection('cv');
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $employees = Employee::all();

        foreach ($employees as $employee) {
            $value = 'contract_' . $employee->first_name . '_' . $employee->last_name;
            $contract = $employee->getMedia('cv')->where('name', $value)->first();
            if ($contract) {
                $contract->delete();
            }
        }
    }
}
