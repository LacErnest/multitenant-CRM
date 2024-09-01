<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use Tenancy\Affects\Connections\Contracts\ResolvesConnections;
use Tenancy\Environment;
use Tenancy\Facades\Tenancy;

class CustomerSeeder extends Seeder
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

        $users = User::where('company_id', $id)->where('role',UserRole::sales()->getIndex())->get();
        foreach ($users as $user){
            $user->customer()->saveMany(factory(App\Models\Customer::class, 50)->create(['company_id' => $id]));
        }
    }
}
