<?php

namespace App\Console\Commands\Scheduler;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Repositories\CreditNoteRepository;
use App\Services\CreditNoteService;
use App\Services\Xero\XeroService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputInterface;
use Tenancy\Environment;

class GetXeroCreditNotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:get_xero_entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get entities from xero and update in system';

    private LegalEntityRepositoryInterface $legalEntityRepository;

    private CompanyRepositoryInterface $companyRepository;

    /**
     * Create a new command instance.
     *
     * @param LegalEntityRepositoryInterface $legalEntityRepository
     * @param CompanyRepositoryInterface $companyRepository
     */
    public function __construct(
        LegalEntityRepositoryInterface $legalEntityRepository,
        CompanyRepositoryInterface $companyRepository
    ) {
        parent::__construct();
        $this->legalEntityRepository = $legalEntityRepository;
        $this->companyRepository = $companyRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(InputInterface $input)
    {
        $legalEntities = $this->legalEntityRepository->getAll(['xeroConfig']);
        $legalEntities->each(function ($legalEntity) {
            if ($legalEntity->xeroConfig && $legalEntity->xeroConfig->xero_tenant_id && $legalEntity->xeroConfig->xero_access_token) {
                try {
                    $creditNotesXero = (new XeroService($legalEntity))->getCreditNotes();

                    foreach ($creditNotesXero as $creditNote) {
                        if ($creditNote->getAllocations()) {
                            $xeroInvoiceId = $creditNote->getAllocations()[0]->getInvoice()->getInvoiceId();
                            $result = $this->getInvoiceFromElastic($xeroInvoiceId);
                            $indexId = $result['hits']['hits'][0]['_index'];

                            $company = $this->getCompany($indexId);
                            $environment = app(Environment::class);
                            $environment->setTenant($company);

                            $credit_note = CreditNote::where('xero_id', $creditNote->getCreditNoteId())->first();

                            if ($credit_note) {
                                (new CreditNoteService(new CreditNoteRepository(new CreditNote)))->update($creditNote);
                            } else {
                                (new CreditNoteService(new CreditNoteRepository(new CreditNote)))->create($creditNote);
                            }
                        }
                    }
                } catch (\Exception $exception) {
                    Log::error($exception->getMessage());
                }
            }
        });
    }

    private function getInvoiceFromElastic(string $invoiceId): array
    {
        $query = [
          'query' => [
              'bool' => [
                  'must' => [
                      ['match' => ['xero_id' => $invoiceId]]
                  ]
              ]
          ]
        ];

        return Invoice::searchAllTenantsQuery('invoices', $query);
    }

    private function getCompany(string $index)
    {
        $id = explode('_', $index)[0];
        $id = substr_replace($id, '-', 8, 0);
        $id = substr_replace($id, '-', 13, 0);
        $id = substr_replace($id, '-', 18, 0);
        $id = substr_replace($id, '-', 23, 0);

        return $this->companyRepository->firstById($id);
    }
}
