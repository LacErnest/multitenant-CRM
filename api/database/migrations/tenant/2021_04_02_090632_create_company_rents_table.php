<?php

use App\Models\CompanyRent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CreateCompanyRentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_rents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('start_date');
            $table->string('name')->nullable();
            $table->decimal('amount', 12)->default(0);
            $table->decimal('admin_amount', 12)->default(0);
            $table->date('end_date')->nullable();
            $table->uuid('author_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $this->deleteAndCreateCompanyRentIndex();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_rents');
    }

    private function deleteAndCreateCompanyRentIndex()
    {
        try {
            CompanyRent::deleteIndex();
        } catch (Exception $e) {
            Log::error('Could not deleted index of company rents. Reason: ' . $e->getMessage());
            Log::error($e);
        }
        CompanyRent::createIndex();

    }
}
