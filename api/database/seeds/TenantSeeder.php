<?php

use App\Models\CompanyRent;
use App\Models\Service;
use Elasticsearch\Common\Exceptions\ScriptLangNotSupportedException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $indexes = indices();
        foreach ($indexes as $model) {
            if (in_array(OnTenant::class, class_uses_recursive($model))
                && $model != Service::class && $model != CompanyRent::class) {
                $model::createIndex();
            }
        }
        if(config('app.env') === 'production' || config('app.env') === 'testing') {
            $this->call(SettingSeeder::class);
        } else {
            $this->call(UserSeeder::class);
            $this->call(CompanyLegalEntitySeeder::class);
            $this->call(TaxRateSeeder::class);
            $this->call(ServiceSeeder::class);
            $this->call(ResourceSeeder::class);
            $this->call(EmployeeSeeder::class);
            $this->call(CustomerSeeder::class);
            $this->call(ContactSeeder::class);
            $this->call(CompanyRentSeeder::class);
            $this->call(ProjectSeeder::class);
            $this->call(CompanyLoanSeeder::class);
            $this->call(SettingSeeder::class);
        }
    }
}
