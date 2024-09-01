<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('xero_id')->nullable();
            $table->unsignedInteger('number')->index();
            $table->unsignedTinyInteger('type')->default(0)->nullable();
            $table->unsignedTinyInteger('status')->default(0)->nullable();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('tax_number')->nullable();
            $table->unsignedTinyInteger('default_currency')->nullable();
            $table->string('phone_number')->nullable();
            $table->uuid('address_id')->nullable();
            $table->decimal('hourly_rate')->nullable();
            $table->decimal('daily_rate')->nullable();
            $table->float('average_rating')->nullable();
            $table->string('job_title')->nullable();
            $table->timestamps();
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('set null');
        });

        DB::statement('ALTER TABLE resources MODIFY number INT(10) UNSIGNED AUTO_INCREMENT');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resources');
    }
}
