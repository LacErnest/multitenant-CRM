<?php

use App\Models\CompanyLoan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmountLeftToCompanyLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_loans', function (Blueprint $table) {
            $table->decimal('amount_left', 12)->default(0)->after('admin_amount');
            $table->decimal('admin_amount_left', 12)->default(0)->after('amount_left');
        });

        $this->setLoanAmountLeft();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_loans', function (Blueprint $table) {
            $table->dropColumn('amount_left', 'admin_amount_left');
        });
    }

    private function setLoanAmountLeft()
    {
        $loans = CompanyLoan::where('paid_at', null)->orderBy('issued_at')->get();
        if ($loans->isNotEmpty()) {
            foreach ($loans as $loan) {
                $loan->amount_left = $loan->amount;
                $loan->admin_amount_left = $loan->admin_amount;
                $loan->save();
            }
        }
    }
}
