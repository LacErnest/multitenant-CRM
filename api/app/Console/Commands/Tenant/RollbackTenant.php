<?php

namespace App\Console\Commands\Tenant;

use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tenancy\Facades\Tenancy;

class RollbackTenant extends RollbackCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:rollback_tenant {name?}
                            {--all}
                            {--pretend : Dump the SQL queries that would be run.}
                            {--step : Force the migrations to be run so they can be rolled back individually.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'rollback last tenant migration by tenant name, use --all for all tenants';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct(app('migrator'));
    }


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if (!$this->confirmToProceed()) {
            return 1;
        }

        $companyService = App::make(CompanyService::class);

        if ($this->option('all')) {
            $companies = $companyService->index();

            foreach ($companies as $company) {
                $connection = $this->prepareConnection($company);

                $this->rollbackMigration($connection);
            }
        } else {
            $company = $companyService->getByName($this->argument('name'));
            $connection = $this->prepareConnection($company);

            $this->rollbackMigration($connection);
        }

        return 0;
    }

    protected function getMigrationPaths(): array
    {
        return [parent::getMigrationPath() . DIRECTORY_SEPARATOR . 'tenant'];
    }

    protected function prepareConnection(Company $company): MySqlConnection
    {
        Tenancy::setTenant($company);

        return tap(DB::connection('tenant'), function ($connection) {
            $connection->reconnect();
        });
    }

    protected function rollbackMigration(MySqlConnection $connection): void
    {
        $this->migrator->usingConnection($connection->getName(), function () {
            $this->migrator->setOutput($this->output)->rollback(
                $this->getMigrationPaths(),
                [
                  'pretend' => $this->option('pretend'),
                  'step' => (int)$this->option('step'),
                ]
            );
        });
    }
}
