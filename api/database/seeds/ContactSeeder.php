<?php

use Illuminate\Database\Seeder;
use App\Enums\UserRole;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Tenancy\Facades\Tenancy;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $connection = Tenancy::getTenantConnectionName();
        $id =  DB::connection($connection)->getDatabaseName();
        $id = substr_replace($id, '-', 8, 0);
        $id = substr_replace($id, '-', 13, 0);
        $id = substr_replace($id, '-', 18, 0);
        $id = substr_replace($id, '-', 23, 0);

        $customers = Customer::where('company_id', $id)->get();

        foreach ($customers as $customer) {
            $contact = factory(App\Models\Contact::class)->create(['customer_id' => $customer->id]);
            $customer->primary_contact_id = $contact->id;
            $customer->save();
        }
    }
}
