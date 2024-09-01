<?php

namespace App\Http\Controllers\Customers;

use App\Enums\TablePreferenceType;
use App\Exports\DataTablesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerAllRequest;
use App\Http\Requests\Customer\CustomerCreateRequest;
use App\Http\Requests\Customer\CustomerGetRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Http\Requests\Export\ExportCustomerGetRequest;
use App\Http\Requests\Import\FinalizeImportFileRequest;
use App\Http\Requests\Import\ImportFileCreateRequest;
use App\Models\Customer;
use App\Models\TablePreference;
use App\Services\CustomerService;
use App\Services\Export\CustomerExporter;
use App\Services\Imports\CustomerImportService;
use App\Http\Resources\Customer\CustomerResource;
use App\Services\TablePreferenceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Jobs\ElasticUpdateAssignment;

class CustomerController extends Controller
{
    protected CustomerService $customer_service;
    protected CustomerImportService $customer_import_service;
    protected string $model = Customer::class;

    public function __construct(CustomerService $customer_service, CustomerImportService $customer_import_service)
    {
        $this->customer_service = $customer_service;
        $this->customer_import_service = $customer_import_service;
    }

    /**
     * Get a customer of a specific company
     * @param $company_id
     * @param CustomerCreateRequest $request
     * @return
     */
    public function index(CustomerGetRequest $request, $company_id, $customer_id): CustomerResource
    {
        $customer = $this->customer_service->get($customer_id);
        return CustomerResource::make($customer);
    }

    /**
     * Get a customers list of a specific company
     * @param string $companyId
     * @param CustomerAllRequest $request
     * @param null $entity
     * @return array
     */
    public function all(string $companyId, CustomerAllRequest $request, $entity = null): array
    {
        return parent::getAll($companyId, $request, TablePreferenceType::customers()->getIndex());
    }


    /**
     * Create a new customer of a specific company
     * @param $company_id
     * @param CustomerCreateRequest $request
     * @return
     */
    public function create($company_id, CustomerCreateRequest $request): CustomerResource
    {
        $customer = $this->customer_service->create($company_id, $request);
        return CustomerResource::make($customer);
    }

    /**
     * Update a customer of a specific company
     * @param $company_id
     * @param $customer_id
     * @param CustomerUpdateRequest $request
     * @return
     */
    public function update(CustomerUpdateRequest $request, $company_id, $customer_id): CustomerResource
    {
        $customer = $this->customer_service->update($customer_id, $request, $company_id);
        ElasticUpdateAssignment::dispatch(getTenantWithConnection(), Customer::class, $customer_id)->onQueue('low');
        return CustomerResource::make($customer);
    }

    /**
     * Get certain customers of a specific company using autocomplete
     * @param $company_id
     * @param $value
     * @param Request $request
     * @return JsonResponse
     */
    public function suggest($company_id, Request $request, $value = null): JsonResponse
    {
        return $this->customer_service->suggest($company_id, $request, $value);
    }

    /**
     * Upload import file for customer of a specific company
     * @param $company_id
     * @param ImportFileCreateRequest $request
     * @return
     */
    public function uploadImportFile(ImportFileCreateRequest $request, $company_id)
    {
        return $this->customer_import_service->saveFile($request);
    }

    /**
     * Import  data from file to customer table of a specific company
     * @param $company_id
     * @param FinalizeImportFileRequest $request
     * @return
     */
    public function finalizeImportFile(FinalizeImportFileRequest $request, $company_id)
    {
        $import = $this->customer_import_service->import($request);

        if (count($import->importData) == 0) {
            throw new BadRequestHttpException();
        }
        $response = parent::getAll($company_id, $request, TablePreferenceType::customers()->getIndex());

        if ($import->haveImportError) {
            $response['notValidFileRows'] = $import->notValidFileRows;
        }
        return $response;
    }

    /**
     * Export data from customer
     * @param ExportCustomerGetRequest $request
     * @param $company_id
     * @param $customer_id
     * @param $type
     * @param $format
     */

    public function export(ExportCustomerGetRequest $request, $company_id, $customer_id, $type, $format)
    {
        if (!$customer = Customer::find($customer_id)) {
            throw new ModelNotFoundException();
        }

        return (new CustomerExporter())->export($customer, $type, $format, $request->input('legal_entity_id'));
    }

    /**
     * Get a customers default currency
     * @param $company_id
     * @param $customer_id
     * @return JsonResponse
     */
    public function getCurrency($company_id, $customer_id)
    {
        $customer = Customer::findOrFail($customer_id);
        return response()->json(['currency' => $customer->default_currency]);
    }

    /**
     * Export all customers to excel
     *
     * @param string $companyId
     * @param CustomerAllRequest $request
     * @return BinaryFileResponse
     */
    public function exportDataTable(string $companyId, CustomerAllRequest $request): BinaryFileResponse
    {
        $amount = Customer::count();
        $request->merge([
          'amount' => $amount,
          'page' => 0,
        ]);

        $tablePrefService = App::make(TablePreferenceService::class);
        $tablePrefs = $tablePrefService->getPreferences('users', TablePreferenceType::customers()->getIndex())->getData();
        $columns = $tablePrefs->columns;
        $customers = parent::getAll($companyId, $request, TablePreferenceType::customers()->getIndex());
        $export = new DataTablesExport($customers['data'], $columns);

        return Excel::download($export, 'customers_' . date('Y-m-d') . '.xlsx');
    }
}
