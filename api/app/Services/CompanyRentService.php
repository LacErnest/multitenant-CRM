<?php

namespace App\Services;

use App\Contracts\Repositories\CompanyRentRepositoryInterface;
use App\Models\CompanyRent;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CompanyRentService
{
    /**
     * @var CompanyRentRepositoryInterface
     */
    protected CompanyRentRepositoryInterface $companyRentRepository;

    public function __construct(CompanyRentRepositoryInterface $companyRentRepository)
    {
        $this->companyRentRepository = $companyRentRepository;
    }

    public function index(int $amount, int $offset, string $sort, string $direction): Collection
    {
        return $this->companyRentRepository->getAllWithTrashed($amount, $offset, [], ['*'], $sort, $direction);
    }

    public function count(): int
    {
        return $this->companyRentRepository->getAllWithTrashed(1000)->count();
    }

    public function view(string $rentId): CompanyRent
    {
        return $this->companyRentRepository->firstById($rentId);
    }

    public function create(array $rentAttributes): CompanyRent
    {
        return $this->companyRentRepository->create($rentAttributes);
    }

    public function update(string $rentId, array $rentAttributes): CompanyRent
    {
        $this->companyRentRepository->update($rentId, $rentAttributes);

        return $this->companyRentRepository->firstById($rentId);
    }

    /**
     * Delete a company monthly costs
     * @param string $rentId
     * @return void
     */
    public function delete(string $rentId): void
    {
        $this->companyRentRepository->delete($rentId, true);
    }
}
