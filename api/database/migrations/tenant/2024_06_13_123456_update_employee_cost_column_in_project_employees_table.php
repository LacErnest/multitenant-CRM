<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEmployeeCostColumnInProjectEmployeesTable extends Migration
{
    public function up()
    {
        Schema::table('project_employees', function (Blueprint $table) {
            $table->decimal('employee_cost', 20, 6)->change();
        });
    }

    public function down()
    {
        Schema::table('project_employees', function (Blueprint $table) {
            $table->decimal('employee_cost')->change();
        });
    }
}
