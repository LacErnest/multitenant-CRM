<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->uuid('contact_id')->nullable();
            $table->uuid('project_manager_id')->nullable();
            $table->uuid('sales_person_id')->nullable();
            $table->decimal('budget', 15, 6)->default(0);
            $table->decimal('budget_usd', 15 , 6)->default(0);
            $table->decimal('employee_costs', 15, 6)->default(0);
            $table->decimal('employee_costs_usd', 16 , 6)->default(0);
            $table->timestamps();

//            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('project_manager_id')->references('id')->on('employees')->onDelete('set null');
//            $table->foreign('sales_person')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
}
