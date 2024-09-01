<?php

use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Models\Employee::class)->create(['type' => \App\Enums\EmployeeType::employee()->getIndex(), 'is_pm' => 1]);
        factory(App\Models\Employee::class, 3)->create(['type' => \App\Enums\EmployeeType::contractor()->getIndex()]);
        factory(App\Models\Employee::class, 7)->create(['type' => \App\Enums\EmployeeType::employee()->getIndex()]);
    }
}
