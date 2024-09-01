<?php

namespace Tests\Traits;

use App\Enums\EmployeeStatus;
use App\Enums\EmployeeType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\LegalEntity;
use App\Models\LegalEntitySetting;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Service;
use App\Models\User;
use Doctrine\Common\Cache\Cache;
use Illuminate\Contracts\Console\Kernel;

trait CreateProject
{
    /**
     * Creates a test project.
     *
     * @return \App\Models\Project
     */
    public function createProject(): Project
    {
        /**
         * @var Customer
         */
        $customer = factory(Customer::class)->create(['company_id' => $this->company->id]);
        /**
         * @var Contact
         */
        $contact = factory(Contact::class)->create(['customer_id' => $customer->id]);
        /**
         * @var LegalEntitySetting
         */
        $legalEntitySetting = factory(LegalEntitySetting::class)->create();
        /**
         * @var LegalEntity
         */
        $legalEntity = factory(LegalEntity::class)->create(['legal_entity_setting_id' => $legalEntitySetting->id]);
        // Let's assign the test legal entity to the company
        $this->company->legalEntities()->attach($legalEntity, ['default' => true]);
        // Let's create test employee
        factory(Employee::class)->create(['type' => EmployeeType::employee()->getIndex(),'status'=>EmployeeStatus::active()->getIndex()]);
        // Let's create test service
        factory(Service::class)->create();
        // Let's create test service
        factory(Resource::class)->create();
        
        // Let's create a project manageur fo the project
        $this->company->users()->save(factory(User::class)->create(['email' =>'pm@example.com','role' =>UserRole::pm()->getIndex()]));
        $salesPerson = $this->company->users()->save(factory(User::class)->create(['email' =>'sales@example.com','role' =>UserRole::sales()->getIndex()]));
        // Let's create the test project
        return factory(Project::class)
            ->create([
                'sales_person_id' => $salesPerson->id,
                'contact_id' => $contact->id,
                'created_at' => $this->company->acquisition_date,
                'budget' => 0
            ]);
    }
}
