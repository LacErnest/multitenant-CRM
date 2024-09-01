<?php

namespace App\Services;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Models\LegalEntity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class CompanyLegalEntityService
 * Get, attach, detach legal entities to a company
 */
class CompanyLegalEntityService
{
    protected CompanyRepositoryInterface $companyRepository;

    protected LegalEntityRepositoryInterface $legalEntityRepository;

    public function __construct(
        CompanyRepositoryInterface $companyRepository,
        LegalEntityRepositoryInterface $legalEntityRepository
    ) {
        $this->companyRepository = $companyRepository;
        $this->legalEntityRepository = $legalEntityRepository;
    }

    public function index(string $companyId)
    {
        return $this->companyRepository->firstById($companyId)->legalEntities;
    }

    public function attach(string $companyId, string $legalEntityId)
    {
        $default = false;
        $company = $this->companyRepository->firstById($companyId);

        if ($this->companyRepository->checkIfAlreadyLinked($company, $legalEntityId)) {
            throw new UnprocessableEntityHttpException('Legal entity already linked to this company.');
        }

        if ($this->companyRepository->checkIfFirstLinked($company)) {
            $default = true;
        }

        return $this->companyRepository->linkLegalEntity($company, $legalEntityId, $default);
    }

    public function detach(string $companyId, string $legalEntityId): void
    {
        $company = $this->companyRepository->firstById($companyId);

        if (!$this->companyRepository->checkIfAlreadyLinked($company, $legalEntityId)) {
            throw new UnprocessableEntityHttpException('Legal entity is not linked to this company.');
        }

        if ($this->companyRepository->checkIsDefault($company, $legalEntityId)) {
            throw new UnprocessableEntityHttpException('This legal entity is set as default, unset it first.');
        }

        $this->companyRepository->unlinkLegalEntity($company, $legalEntityId);
    }

    public function setDefault(string $companyId, string $legalEntityId): void
    {
        $company = $this->companyRepository->firstById($companyId);

        if (!$this->companyRepository->checkIfAlreadyLinked($company, $legalEntityId)) {
            throw new UnprocessableEntityHttpException('Legal entity is not linked to this company.');
        }

        if ($this->companyRepository->checkIsDefault($company, $legalEntityId)) {
            throw new UnprocessableEntityHttpException('This legal entity is already set as default.');
        }

        $this->companyRepository->setLegalEntityAsDefault($company, $legalEntityId);
    }

    public function suggest(string $value): array
    {
        if (!($value === null)) {
            $query_array = explode(' ', str_replace(['"', '+', '=', '-', '&&', '||', '>', '<', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '\\', '/'], '?', trim($value)));
            $query_array = array_filter($query_array);
            $query_string = implode(' ', $query_array);
            $value = strtolower($query_string);
        }

        $searchQuery = [];
        $limit = 5;

        array_push(
            $searchQuery,
            [
                'bool' => [
                    'should' => [
                        [
                            'wildcard' => [
                                'name.keyword' => [
                                    'value' => '*' . $value . '*'
                                ]
                            ]
                        ],
                        [
                            'wildcard' => [
                                'name.keyword' => [
                                    'value' => $value . '*',
                                    'boost' => 5
                                ]
                            ]
                        ],
                        [
                            'wildcard' => [
                                'name.keyword' => [
                                    'value' => $value,
                                    'boost' => 10
                                ]
                            ]
                        ],
                    ],
                ],
            ]
        );

        $query = [
            'query' => [
                'bool' => [
                    'must' => array_merge(
                        [
                            ['bool' => [
                                'must_not' => [
                                    ['exists' => ['field' => 'deleted_at']]
                                ],
                            ]],
                        ],
                        $searchQuery
                    )
                ],
            ],
        ];

        $legalEntities = LegalEntity::searchBySingleQuery($query, $limit);

        $result = $legalEntities['hits']['hits'];

        $result = array_map(function ($r) {
            $r =  Arr::only($r['_source'], ['id', 'name']);

            return $r;
        }, $result);

        return $result;
    }

    public function isDefault(string $companyId, string $legalEntityId): bool
    {
        $company = $this->companyRepository->firstById($companyId);
        return $this->companyRepository->checkIsDefault($company, $legalEntityId);
    }

    public function setLocal(string $companyId, string $legalEntityId): void
    {
        $company = $this->companyRepository->firstById($companyId);

        if (!$this->companyRepository->checkIfAlreadyLinked($company, $legalEntityId)) {
            throw new UnprocessableEntityHttpException('Legal entity is not linked to this company.');
        }

        if ($this->companyRepository->checkIsLocal($company, $legalEntityId)) {
            throw new UnprocessableEntityHttpException('This legal entity is already set as local.');
        }

        $this->companyRepository->setLegalEntityAsLocal($company, $legalEntityId);
    }

    public function checkIfValid(string $companyId, string $legalEntityId): bool
    {
        $company = $this->companyRepository->firstById($companyId);
        return $this->companyRepository->checkIslocal($company, $legalEntityId) || $this->companyRepository->checkIsDefault($company, $legalEntityId);
    }
}
