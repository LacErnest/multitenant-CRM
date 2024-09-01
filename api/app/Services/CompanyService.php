<?php

namespace App\Services;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Models\Company;

/**
 * Class CompanyService
 * Manage companies
 */
class CompanyService
{
    protected CompanyRepositoryInterface $companyRepository;

    public function __construct(CompanyRepositoryInterface $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    public function index()
    {
        return $this->companyRepository->getAll();
    }

    public function getByName(string $companyName): Company
    {
        return $this->companyRepository->firstBy('name', $companyName);
    }

    public function suggest($value)
    {
        return Company::where('name', 'like', "%$value%")->select('id', 'name')->get();
    }
}
