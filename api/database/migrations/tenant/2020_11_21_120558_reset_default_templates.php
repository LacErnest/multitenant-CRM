<?php

use App\Models\Setting;
use App\Repositories\TemplateRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ResetDefaultTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if($settings = Setting::first()) {
            (new TemplateRepository($settings))->addDefaultTemplates();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // N/A
    }
}
