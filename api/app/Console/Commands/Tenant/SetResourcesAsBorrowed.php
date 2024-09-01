<?php

namespace App\Console\Commands\Tenant;

use App\Contracts\Repositories\ResourceRepositoryInterface;
use App\Models\Company;
use App\Models\Resource;
use Exception;
use Illuminate\Console\Command;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;
use Tenancy\Facades\Tenancy;

class SetResourcesAsBorrowed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:set_resources_as_borrowed {name} {value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a company his resources as borrowable or not,
                              use the name of the company followed by on or off';

    protected ResourceRepositoryInterface $resourceRepository;

    /**
     * Create a new command instance.
     *
     * @param ResourceRepositoryInterface $resourceRepository
     */
    public function __construct(ResourceRepositoryInterface $resourceRepository)
    {
        parent::__construct();
        $this->resourceRepository = $resourceRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $companyName = $this->argument('name');
        $value = $this->argument('value');
        if ($value == 'on') {
            $toggle = true;
            $message = 'Company ' . $companyName . ' his resources can be borrowed now.';
        } elseif ($value == 'off') {
            $toggle = false;
            $message = 'Company ' . $companyName . ' his resources can no longer be borrowed.';
        } else {
            $this->line('You can only use on or off.');
            return 1;
        }

        $company = Company::where('name', $companyName)->first();

        if ($company) {
            Tenancy::setTenant($company);
            $resources = $this->resourceRepository->getAll()->pluck('id')->toarray();
            $this->resourceRepository->massUpdate($resources, ['can_be_borrowed' => $toggle]);
            $model = Resource::class;

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

            $this->line($message);
        } else {
            $this->line('Company ' . $companyName . ' not found');
        }

        return 0;
    }
}
