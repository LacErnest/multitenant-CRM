<?php

use App\Models\Invoice;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\Template;
use App\Repositories\TemplateRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Setting::create([
            'id'                           => Str::uuid(),
        ]);
    }
}
