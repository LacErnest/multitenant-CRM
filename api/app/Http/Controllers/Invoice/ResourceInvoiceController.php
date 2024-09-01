<?php

namespace App\Http\Controllers\Invoice;

use App\Enums\InvoiceType;
use App\Enums\TablePreferenceType;
use App\Exports\DataTablesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\InvoicesGetRequest;
use App\Models\Invoice;
use App\Models\ResourceInvoice;
use App\Models\TablePreference;
use App\Services\InvoiceService;
use App\Services\ItemService;
use App\Services\TablePreferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResourceInvoiceController extends Controller
{
    protected string $model = ResourceInvoice::class;

    public function __construct(Itemservice $item_service, InvoiceService $invoice_service)
    {
        parent::__construct($item_service);
    }

    /**
     * Get all invoices of a specific company
     * @param string $companyId
     * @param Request $request
     * @param null $entity
     * @return array
     */
    public function getAll(string $companyId, Request $request, $entity = null): array
    {
        return parent::getAll($companyId, $request, TablePreferenceType::resource_invoices()->getIndex());
    }

    /**
     * Export all resource invoices to excel
     *
     * @param string $companyId
     * @param InvoicesGetRequest $request
     * @return BinaryFileResponse
     */
    public function exportDataTable(string $companyId, InvoicesGetRequest $request): BinaryFileResponse
    {
        $amount = Invoice::where('type', InvoiceType::accpay()->getIndex())
          ->whereNotNull('purchase_order_id')->count();
        $request->merge([
          'amount' => $amount,
          'page' => 0,
        ]);

        $tablePreferenceType = TablePreferenceType::resource_invoices();
        if (!empty($request->project)) {
            $tablePreferenceType = TablePreferenceType::project_resource_invoices();
        }
        $tablePrefService = App::make(TablePreferenceService::class);
        $tablePrefs = $tablePrefService->getPreferences('users', $tablePreferenceType->getIndex())->getData();
        $columns = $tablePrefs->columns;
        $resource_invoices = parent::getAll($companyId, $request, $tablePreferenceType->getIndex());
        $export = new DataTablesExport($resource_invoices['data'], $columns);

        return Excel::download($export, 'resourceInvoices_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Get all resource invoices belonging to a project
     * @param string $companyId
     * @param string $projectId
     * @param Request $request
     * @param null $entity
     * @return array
     */
    public function getAllFromProject(string $companyId, string $projectId, Request $request, $entity = null): array
    {
        $request['project'] = $projectId;
        return parent::getAll($companyId, $request, TablePreferenceType::project_resource_invoices()->getIndex());
    }
}
