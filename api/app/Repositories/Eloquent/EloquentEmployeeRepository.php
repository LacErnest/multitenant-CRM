<?php


namespace App\Repositories\Eloquent;


use App\Contracts\Repositories\EmployeeRepositoryInterface;
use App\Models\Employee;

class EloquentEmployeeRepository extends EloquentRepository implements EmployeeRepositoryInterface
{
    public const CURRENT_MODEL = Employee::class;
}
