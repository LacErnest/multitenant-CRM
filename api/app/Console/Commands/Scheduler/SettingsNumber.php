<?php

namespace App\Console\Commands\Scheduler;

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Models\Company;
use Illuminate\Console\Command;
use App\Models\Setting;
use Tenancy\Facades\Tenancy;

class SettingsNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:settings_number {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset number in settings';

    protected LegalEntityRepositoryInterface $legalEntityRepository;

    /**
     * Create a new command instance.
     *
     * @param LegalEntityRepositoryInterface $legalEntityRepository
     */
    public function __construct(LegalEntityRepositoryInterface $legalEntityRepository)
    {
        parent::__construct();
        $this->legalEntityRepository = $legalEntityRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->argument('type');

        $legalEntities = $this->legalEntityRepository->getAll(['legalEntitySetting']);
        $legalEntities->each(function ($legalEntity) use ($type) {

            if ($this->checkResetFormat($type, $legalEntity->legalEntitySetting->quote_number_format)) {
                $legalEntity->legalEntitySetting->quote_number = 0;
            }
            if ($this->checkResetFormat($type, $legalEntity->legalEntitySetting->order_number_format)) {
                $legalEntity->legalEntitySetting->order_number = 0;
            }
            if ($this->checkResetFormat($type, $legalEntity->legalEntitySetting->invoice_number_format)) {
                $legalEntity->legalEntitySetting->invoice_number = 0;
            }
            if ($this->checkResetFormat($type, $legalEntity->legalEntitySetting->purchase_order_number_format)) {
                $legalEntity->legalEntitySetting->purchase_order_number = 0;
            }
            if ($this->checkResetFormat($type, $legalEntity->legalEntitySetting->resource_invoice_number_format)) {
                $legalEntity->legalEntitySetting->resource_invoice_number = 0;
            }
            $legalEntity->legalEntitySetting->save();
        });
        return true;
    }

    private function checkResetFormat($type, $format)
    {
        if ($type == 'month') {
            if ($this->striposArray($format, ['[MM]','[MMMM]'])) {
                return true;
            }
        }

        if ($type == 'year') {
            if ($this->striposArray($format, ['[YY]','[YYYY]'])) {
                return true;
            }
        }

        if ($type == 'quarter') {
            if ($this->striposArray($format, ['[Q]'])) {
                return true;
            }
        }

        return false;
    }

    private function striposArray($string, $array)
    {
        foreach ($array as $item) {
            if (stripos($string, $item) !== false) {
                return true;
            }
        }
        return false;
    }
}
