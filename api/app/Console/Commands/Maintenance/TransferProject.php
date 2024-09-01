<?php

namespace App\Console\Commands\Maintenance;

use App\Enums\InvoiceType;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Media;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Tenancy\Facades\Tenancy;

class TransferProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:transfer_project {companyA : The id of companyA A}
                            {companyB : The id of companyB} {projectId : The id of the project}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer a project from one company to an other';

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
        $companyA = Company::findOrfail($this->argument('companyA'));
        $companyB = Company::findOrfail($this->argument('companyB'));
        $projectId = $this->argument('projectId');

        Tenancy::setTenant($companyA);
        $project = Project::with(
            'order',
            'order.items',
            'order.items.priceModifiers',
            'order.priceModifiers',
            'quotes',
            'quotes.items',
            'quotes.items.priceModifiers',
            'quotes.priceModifiers',
            'invoices',
            'invoices.items',
            'invoices.items.priceModifiers',
            'invoices.priceModifiers',
            'purchaseOrders',
            'purchaseOrders.items',
            'purchaseOrders.items.priceModifiers',
            'purchaseOrders.priceModifiers',
            'contact'
        )->findOrFail($projectId);

        Tenancy::setTenant($companyB);
        $newProject = $this->createProject($project);

        foreach ($project->quotes as $quote) {
            $this->cloneEntity($quote, $newProject->id);
        }

        $this->cloneEntity($project->order, $newProject->id);

        foreach ($project->purchaseOrders as $po) {
            $this->cloneEntity($po, $newProject->id);
        }

        foreach ($project->invoices as $invoice) {
            $media = null;
            if (InvoiceType::isAccpay($invoice->type)) {
                Tenancy::setTenant($companyA);
                if ($invoice->hasMedia('invoice_uploads')) {
                    $media = $invoice->getFirstMedia('invoice_uploads')->getPath();
                }
                Tenancy::setTenant($companyB);
            }
            $this->cloneEntity($invoice, $newProject->id, $media);
        }

        $this->line('Project transferred.');

        return 0;
    }

    private function createProject($project)
    {
        $salesId = null;
        if ($project->sales_person_id) {
            $salesPerson = User::find($project->sales_person_id);

            if ($salesPerson) {
                $salesExists = User::where([['company_id', $this->argument('companyB')], ['email', $salesPerson->email]])->first();
                if ($salesExists) {
                    $salesId = $salesExists->id;
                }
            }
        }

        return Project::create([
          'name' => $project->name,
          'contact_id' => $project->contact_id,
          'project_manager_id' => null,
          'sales_person_id' => $salesId,
          'created_at' => $project->created_at,
          'updated_at' => $project->updated_at,
        ]);
    }

    private function cloneEntity($entity, $projectId, $media = null)
    {
        $clone = $entity->replicate();
        $clone->id = $entity->id;
        $clone->project_id = $projectId;
        $clone->created_at = $entity->created_at;
        $clone->updated_at = $entity->updated_at;
        $clone->save();

        foreach ($entity->getRelations() as $relation => $items) {
            if ($relation != 'media') {
                foreach ($items as $item) {
                    unset($item->id);
                    $new_item = $clone->{$relation}()->create($item->toArray());
                    foreach ($item->getRelations() as $deepRelation => $deepItems) {
                        foreach ($deepItems as $deepItem) {
                            unset($deepItem->id);
                            $new_item->{$deepRelation}()->create($deepItem->toArray());
                        }
                    }
                }
            }
        }

        if ($media) {
            $clone->addMedia($media)->preservingOriginal()->usingFileName($clone->id . '.pdf')->toMediaCollection('invoice_uploads');
        }
    }
}
