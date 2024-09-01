<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;
use Tenancy\Facades\Tenancy;
use Tenancy\Identification\Contracts\Tenant;
use Tests\Traits\CreatesApplication;
use Faker\Factory as FakerFactory;

abstract class TenantTestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    public ?Tenant $tenant = null;
    /**
     * @var Company
     */
    public ?Company $company = null;
    /**
     * @var FakerFactory
     */
    public $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = FakerFactory::create();

      $this->initializeElastic();

    if (!isset($this->tenant)) {
        $this->initializeTenancy();
    }
  }

    public function tearDown(): void
    {
        $this->cleanupElastic();
        $this->cleanupTenancy();

      parent::tearDown();
  }

    protected function initializeTenancy(): void
    {
        $this->tenant = factory(Company::class)->create();
        Tenancy::setTenant($this->tenant);
        $this->company = Company::findOrFail($this->tenant->id);
        $this->initializeUsers();
    }

    protected function initializeUsers(): void
    {
        $this->company->users()->save(factory(User::class)->create(['email' => 'nonadmin@test.test', 'google2fa' => true, 'role' => UserRole::owner()->getIndex()]));
    }

    protected function cleanupTenancy(): void
    {
        Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->dropDatabase("`{$this->tenant->getTenantKey()}`");
    }

    protected function cleanupElastic(): void
    {
        $indexes = indices();

        foreach ($indexes as $model) {
            $model::deleteIndex();
        }
    }

    protected function initializeElastic(): void
    {
        $indexes = indices();

        foreach ($indexes as $model) {
            try {
                $model::deleteIndex();
            } catch (Exception $e) {
                Log::error($e->getMessage(), [$e]);
            }

            if (!in_array(OnTenant::class, class_uses_recursive($model))) {
                $model::createIndex();
            }
        }
    }
}
