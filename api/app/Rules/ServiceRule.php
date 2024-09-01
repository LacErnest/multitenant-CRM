<?php

namespace App\Rules;

use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\Service;
use Illuminate\Contracts\Validation\ImplicitRule;
use phpDocumentor\Reflection\Types\Nullable;
use Tenancy\Facades\Tenancy;

class ServiceRule implements ImplicitRule
{
    protected string $class;
    protected string $entityId;
    protected $serviceId;
    protected $tenantId;

    /**
     * Create a new rule instance.
     *
     * @param string $class
     * @param string $entityId
     * @param string|null $serviceId
     * @param string|null $tenantId
     */
    public function __construct(string $class, string $entityId, $serviceId, $tenantId = null)
    {
        $this->class = $class;
        $this->entityId = $entityId;
        $this->serviceId = $serviceId;
        $this->tenantId = $tenantId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->class == PurchaseOrder::class) {
            return true;
        }

        $entity = $this->class::findOrFail($this->entityId);

        if (!$entity->manual_input && !$entity->master) {
            if (!$this->serviceId) {
                return false;
            } else {
                $service = $this->getService();

                if (!$service) {
                    return false;
                }
            }
        }
        return true;
    }

    private function getService()
    {
        if (!empty($this->tenantId)) {
            $curentTenantId = getTenantWithConnection();
            Tenancy::setTenant(Company::find($this->tenantId));
        }
        $service = Service::find($this->serviceId);
        if (!empty($curentTenantId)) {
            Tenancy::setTenant(Company::find($curentTenantId));
        }
        return $service;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'A valid service is required';
    }
}
