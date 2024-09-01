<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use App\Enums\UserRole;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $isPrimary = false;
        $company = Company::orderBy('created_at', 'DESC')->first();

        if(!User::exists()){
            factory(App\Models\User::class)->create(['email' => 'karl.quigley@magicmedia.studio','role' => UserRole::admin()->getIndex()]);
            factory(App\Models\User::class)->create(['email' => 'mathieu@cyrextech.net','google2fa_secret' => Crypt::encryptString('bufyqaworkxcaykd'),'google2fa' => true,
                'role' => UserRole::admin()->getIndex(), 'super_user' => true]);
            factory(App\Models\User::class)->create(['email' => 'tim@cyrextech.net','google2fa_secret' => Crypt::encryptString('uaaOYwwdWOQilNeZ'),'google2fa' => true,
                'role' => UserRole::admin()->getIndex(), 'super_user' => true]);
            factory(App\Models\User::class)->create(['email' => 'ruben.lamoot@mogi-group.com','google2fa_secret' => Crypt::encryptString('mecbevfmooklrsco'),'google2fa' => true,
                'role' => UserRole::admin()->getIndex(), 'super_user' => true]);
            factory(App\Models\User::class)->create(['email' => 'pepijn.de-wachter@cyrextech.net', 'role' => UserRole::admin()->getIndex(), 'google2fa' => true]);
            factory(App\Models\User::class)->create(['email' => 'douglas.wafo@magicmedia.studio', 'role' => UserRole::admin()->getIndex(), 'google2fa' => false,'super_user'=>true]);
            factory(App\Models\User::class)->create(['email' => 'marcia.cruz@magicmedia.studio', 'role' => UserRole::admin()->getIndex(), 'google2fa' => false]);
            factory(App\Models\User::class)->create(['email' => 'david.rosario@magicmedia.studio', 'role' => UserRole::admin()->getIndex(), 'google2fa' => true,'super_user'=>true]);
            factory(App\Models\User::class)->create(['email' => 'ernest.tsamo@magicmedia.studio', 'role' => UserRole::admin()->getIndex(), 'google2fa' => false,'super_user'=>true]);
            factory(App\Models\User::class)->create(['email' => 'owner@magicmedia.studio', 'role' => UserRole::owner()->getIndex(), 'google2fa' => true,'super_user'=>false]);

            $isPrimary = true;
        }

        $company->users()->save(factory(App\Models\User::class)->create(['email' => 'nonadmin@test.test', 'google2fa' => true, 'primary_account' => $isPrimary, 'role' => rand(UserRole::accountant()->getIndex(), UserRole::hr()->getIndex())]));
        $company->users()->saveMany(factory(App\Models\User::class,16)->create(['google2fa' => true]));
        factory(App\Models\User::class,4)->create(['role' => UserRole::admin()->getIndex()]);
    }
}
