<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use App\Models\Contact;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        config(['mail.driver' => 'log']);

        config(['settings.seeding' => true]);
        Artisan::call('dev:cache_currency_rates');
        $indexes = indices();

        foreach ($indexes as $model) {
            try {
                $model::deleteIndex();
            } catch (Exception $e) {
                logger($e->getMessage());
            }
            if (!in_array(OnTenant::class, class_uses_recursive($model))) {
                $model::createIndex();
            }
        }

        $this->call(LegalEntitySeeder::class);
        $this->call(LegalEntitySettingsSeeder::class);
        $this->call(CompanySeeder::class);
        $this->call(CompanySettingSeeder::class);
        Artisan::call('maintenance:update_entity_price');
        Artisan::call('maintenance:elastic_refresh');
        config(['settings.seeding' => false]);
        config(['mail.driver' => 'smtp']);
    }
}
