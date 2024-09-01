<?php

namespace App\Console\Commands\Tenant;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Tenancy\Environment;
use Tenancy\Identification\Contracts\ResolvesTenants;

class DeleteImportFiles extends Command
{
    private array $importCollections = ['imports_customers','imports_resources'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:imports_delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old  import files';

    protected $resolver;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ResolvesTenants $resolver)
    {
        parent::__construct();
        $this->resolver = $resolver;
    }


    public function handle(InputInterface $input)
    {
        $this->resolver->getModels()->each(function (string $class) use ($input) {
            $model = (new $class());
            $model->all()->each(function ($tenant) {
                $environment = app(Environment::class);
                $environment->setTenant($tenant);
            });
            foreach ($this->importCollections as $importCollection) {
                $files = (new Setting())->first()->getMedia($importCollection)->where('created_at', '<=', Carbon::now()->subDay());
                foreach ($files as $file) {
                    $file->delete();
                }
            }
        });
    }
}
