<?php

namespace App\Services;

use App\Contracts\Repositories\CompanyLoanRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyLoan;
use App\Models\CompanyRent;
use App\Models\EmployeeHistory;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class CompanyLoanService
 * Issue and manage loans to companies
 */
class CompanyLoanService
{
    protected CompanyLoanRepositoryInterface $companyLoanRepository;

    public function __construct(CompanyLoanRepositoryInterface $companyLoanRepository)
    {
        $this->companyLoanRepository = $companyLoanRepository;
    }

    public function index(int $amount, int $offset, string $sort, string $direction): Collection
    {
        return $this->companyLoanRepository->getAllWithTrashed($amount, $offset, [], ['*'], $sort, $direction);
    }

    public function view(string $loanId): CompanyLoan
    {
        return $this->companyLoanRepository->firstById($loanId);
    }

    public function create(array $loanAttributes): CompanyLoan
    {
        return $this->companyLoanRepository->create($loanAttributes);
    }

    public function update(string $loanId, array $loanAttributes): CompanyLoan
    {
        $loan = $this->companyLoanRepository->firstById($loanId);

        if ($loan->paid_at) {
            throw new UnprocessableEntityHttpException('Loan is paid off, you can\'t update it.');
        }
        if ($loanAttributes['amount'] > $loan->amount) {
            $loanAttributes['amount_left'] = $loan->amount_left + ($loanAttributes['amount'] - $loan->amount);
            $loanAttributes['admin_amount_left'] = $loan->admin_amount_left + ($loanAttributes['admin_amount'] - $loan->admin_amount);
        } elseif ($loanAttributes['amount'] < $loan->amount) {
            $loanAttributes['amount_left'] = $loan->amount_left - ($loan->amount - $loanAttributes['amount']);
            $loanAttributes['admin_amount_left'] = $loan->admin_amount_left - ($loan->admin_amount - $loanAttributes['admin_amount']);
            if ($loanAttributes['amount_left'] < 0) {
                $loanAttributes['amount_left'] = 0;
                $loanAttributes['admin_amount_left'] = 0;
            }
        }

        $this->companyLoanRepository->update($loanId, $loanAttributes);

        return $loan->refresh();
    }

    /**
     * Delete a company loan according to the specified loan id
     * @param string $loanId
     * @return void
     * @throws UnprocessableEntityHttpException
     */
    public function delete(string $loanId): void
    {
        /**
         * @var CompanyLoan
         */
        $loan = $this->companyLoanRepository->firstById($loanId);
        /**
         * If there is no payment logs, then just delete the loan from the database
         * @var bool
         */
        $isPaidOff = isset($loan->paid_at);
        /**
         * @var bool
         */
        $isCreatedMoreThenThreeDaysAgo = $loan->created_at->lessThan(Carbon::today()->subDays(3));

        if (!$isPaidOff && !$isCreatedMoreThenThreeDaysAgo) {
            $this->companyLoanRepository->delete($loanId, true);
        } else {
            throw new UnprocessableEntityHttpException('This loan can\'t be deleted.');
        }
    }

    public function count(): int
    {
        return $this->companyLoanRepository->getAllWithTrashed(1000)->count();
    }
}
