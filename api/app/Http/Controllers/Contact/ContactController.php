<?php

namespace App\Http\Controllers\Contact;

use App\Enums\TablePreferenceType;
use App\Exports\DataTablesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\ContactGetRequest;
use App\Models\Contact;
use App\Models\TablePreference;
use App\Services\ContactService;
use App\Services\TablePreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


class ContactController extends Controller
{
    protected ContactService $contact_service;
    protected string $model = Contact::class;

    public function __construct(ContactService $contact_service)
    {
        $this->contact_service = $contact_service;
    }

    /**
     * Get a resource list of a specific company
     * @param string $companyId
     * @param ContactGetRequest $request
     * @param null $entity
     * @return array
     */
    public function all(string $companyId, ContactGetRequest $request, $entity = null): array
    {
        return parent::getAll($companyId, $request, TablePreferenceType::contacts()->getIndex());
    }

    /**
     * Get certain users of a specific company using autocomplete
     * @param $company_id
     * @param $value
     * @param Request $request
     * @return JsonResponse
     */
    public function suggest($company_id, Request $request, $value = null): JsonResponse
    {
        return $this->contact_service->suggest($company_id, $request, $value);
    }

    /**
     * Export all contacts to excel
     *
     * @param string $companyId
     * @param ContactGetRequest $request
     * @return BinaryFileResponse
     */
    public function exportDataTable(string $companyId, ContactGetRequest $request): BinaryFileResponse
    {
        $amount = Contact::count();
        $request->merge([
          'amount' => $amount,
          'page' => 0,
        ]);

        $tablePrefService = App::make(TablePreferenceService::class);
        $tablePrefs = $tablePrefService->getPreferences('users', TablePreferenceType::contacts()->getIndex())->getData();
        $columns = $tablePrefs->columns;
        $contacts = parent::getAll($companyId, $request, TablePreferenceType::contacts()->getIndex());
        $export = new DataTablesExport($contacts['data'], $columns);

        return Excel::download($export, 'contacts_' . date('Y-m-d') . '.xlsx');
    }
}
