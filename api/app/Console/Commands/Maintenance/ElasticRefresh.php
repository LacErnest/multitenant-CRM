<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Company;
use Exception;
use Illuminate\Console\Command;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;
use Tenancy\Facades\Tenancy;

class ElasticRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:elastic_refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex all documents';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $indexes = indices();
        $this->line('Starting refreshing global indexes.');
        foreach ($indexes as $model) {
            if (!in_array(OnTenant::class, class_uses_recursive($model))) {
                try {
                    $model::deleteIndex();
                } catch (Exception $e) {
                    $this->line('Could not deleted index of ' . $model . '. Reason: ' . $e->getMessage());
                }
                $model::createIndex();
                $model::addAllToIndexWithoutScopes();
                $this->line('<fg=green>Refreshed index for model</> <fg=green;bg=black;options=bold>' . $model . '</>');
            }
        }
        $companies = Company::all();
        foreach ($companies as $company) {
            Tenancy::setTenant($company);
            $this->line('Starting refreshing indexes of tenant ' . $company->name . '.');
            foreach ($indexes as $model) {
                if (in_array(OnTenant::class, class_uses_recursive($model))) {
                    try {
                        $model::deleteIndex();
                    } catch (Exception $e) {
                        $this->line('Could not deleted index of ' . $model . '. Reason: ' . $e->getMessage());
                    }
                    $model::createIndex();
                    $model::addAllToIndexWithoutScopes();
                    $this->line('<fg=green>Refreshed index for model</> <fg=green;bg=black;options=bold>' . $model . '</>');
                }
            }
        }
        return 0;
    }
}
