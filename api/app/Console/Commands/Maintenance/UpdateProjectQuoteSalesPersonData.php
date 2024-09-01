<?php

namespace App\Console\Commands\Maintenance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Traits\Models\UpdateSalesPersonRelationship;

class UpdateProjectQuoteSalesPersonData extends Command
{

    use UpdateSalesPersonRelationship;

    protected $signature = 'maintenance:update_project_quote_sales_persons';
    protected $description = 'Update existing project and quote sales persons';

    public function handle()
    {


        $this->updateSalesPersonRelationship();

        $this->info('Project data migrated successfully!');

        $this->info('Refreshing Elasticsearch indexes...');
        $this->line(Artisan::output());

        Artisan::call('maintenance:elastic_refresh');
        $this->line(Artisan::output());
    }
}
