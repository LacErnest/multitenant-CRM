<?php

use App\Models\SmtpSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateCompanySmtpSettingsToSmtpSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            $companyId = getTenantWithConnection();
            $companySmtpSettings = DB::connection('mysql')
                ->table('company_smtp_settings')
                ->where('company_id', '=', $companyId)
                ->get();

            foreach ($companySmtpSettings as $setting) {
                $arraySetting = json_decode(json_encode($setting), true);
                $arraySetting = array_merge($arraySetting, [
                    'sender_email' => $setting->from_email,
                    'sender_name' => $setting->from_name
                ]);
                SmtpSetting::create($arraySetting);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
