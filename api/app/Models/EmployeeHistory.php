<?php

namespace App\Models;

use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class EmployeeHistory extends Model
{
    use uuids;
    use OnTenant;
    use AutoElastic;

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'salary',
        'salary_usd',
        'currency_rate',
        'employee_salary',
        'currency_rate_employee',
        'default_currency',
        'working_hours'
    ];

    protected $dates = ['created_at', 'updated_at', 'start_date', 'end_date'];

    protected $mappingProperties = [
        'id' => [
            'type' => 'keyword'
        ],
        'employee_id' => [
            'type' => 'keyword'
        ],
        'salary' => [
            'type' => 'float',
        ],
        'working_hours' => [
            'type' => 'float',
        ],
        'salary_usd' => [
            'type' => 'float',
        ],
        'start_date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'end_date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'created_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'updated_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'currency_rate' => [
            'type' => 'float',
        ],
        'currency_rate_employee' => [
            'type' => 'float',
        ],
        'employee_salary' => [
            'type' => 'float',
        ],
        'default_currency' => [
            'type' => 'keyword',
        ],
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
