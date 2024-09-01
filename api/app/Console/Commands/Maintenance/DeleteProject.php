<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Company;
use App\Models\Project;
use Illuminate\Console\Command;
use Tenancy\Facades\Tenancy;

class DeleteProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:delete_project {company : The id of the company}
                            {projectId : The id of the project}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a project';

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
        $company = Company::findOrfail($this->argument('company'));
        $projectId = $this->argument('projectId');

        Tenancy::setTenant($company);

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
            'purchaseOrders.priceModifiers'
        )->findOrFail($projectId);

        foreach ($project->quotes as $quote) {
            foreach ($quote->items as $item) {
                $item->priceModifiers()->delete();
                $item->delete();
            }
            $quote->priceModifiers()->delete();
            $quote->delete();
        }

        $order = $project->order;
        if ($order) {
            foreach ($order->items as $item) {
                $item->priceModifiers()->delete();
                $item->delete();
            }
            $order->priceModifiers()->delete();
            $order->delete();
        }

        foreach ($project->invoices as $invoice) {
            foreach ($invoice->items as $item) {
                $item->priceModifiers()->delete();
                $item->delete();
            }
            $invoice->priceModifiers()->delete();
            $invoice->delete();
        }

        foreach ($project->purchaseOrders as $po) {
            foreach ($po->items as $item) {
                $item->priceModifiers()->delete();
                $item->delete();
            }
            $po->priceModifiers()->delete();
            $po->delete();
        }

        $project->employees()->detach();
        $project->delete();

        $this->line('Project deleted.');

        return 0;
    }
}
