<?php

namespace App\Http\Resources\Project;

use App\Enums\CommissionModel;
use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\TablePreferenceType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\ProjectEmployee;
use App\Models\SalesCommission;
use App\Models\SalesCommissionPercentage;
use App\Models\TablePreference;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\EmployeeService;
use App\Services\ResourceService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Tenancy\Facades\Tenancy;
use App\Traits\Models\FormatProjectCommissions;

class ProjectResource extends JsonResource
{
    use FormatProjectCommissions;
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = null;
        $employeeService = App::make(EmployeeService::class);

        $projectEmployees = ProjectEmployee::where('project_id', $this->id)->orderBy('created_at')->get();
        $tablePrefs = TablePreference::where([['user_id', auth()->user()->id], ['type', TablePreferenceType::project_employees()->getIndex()]])->first();
        if ($tablePrefs) {
            $tableSorts = json_decode($tablePrefs->sorts, true);
            if ($tableSorts) {
                if ($tableSorts[0]['dir'] == 'asc') {
                    $projectEmployees = $projectEmployees->sortBy($tableSorts[0]['prop']);
                } else {
                    $projectEmployees = $projectEmployees->sortByDesc($tableSorts[0]['prop']);
                }
            }
        }

        $company = Company::find(getTenantWithConnection());
        $invoices = $this->invoices()->where([
            ['type', InvoiceType::accrec()->getIndex()],
            ['status', InvoiceStatus::paid()->getIndex()]
          ])->orderByDesc('updated_at')->get();

        $projectCommissions = [];
        $salesPersons = $this->salesPersons ?? collect();
        $leadGens = $this->leadGens ?? collect();

        if (!is_iterable($salesPersons) || !is_iterable($leadGens)) {
            throw new \Exception('Expected iterable for salesPersons and leadGens');
        }

        $salesPeoples = [];

        foreach ($salesPersons as $salesPerson) {
            if ($salesPerson && !in_array($salesPerson->id, array_column($salesPeoples, 'id'))) {
                $salesPeoples[] = $salesPerson;
            }
        }

        foreach ($leadGens as $leadGen) {
            if ($leadGen && !in_array($leadGen->id, array_column($salesPeoples, 'id'))) {
                $salesPeoples[] = $leadGen;
            }
        }

        $salesPeoples = array_filter($salesPeoples);
        $salesPeoples = collect($salesPeoples)->unique('id')->values()->all();
        $projectCommissions = $this->projectCommissions($salesPeoples, $this, $company);
        $totalProjectCommissions = 0;
        foreach ($projectCommissions as $projectCommission ) {
            $totalProjectCommissions += $projectCommission['total_commission'];
        }
        $totalInvoiceValue = $invoices->sum('total_price');
        $projectCommissionPercentage = safeDivide($totalProjectCommissions, $totalInvoiceValue) * 100;
        $exceedsThreshold = $projectCommissionPercentage > 3;
        $euro = false;

        if ($company->currency_code == CurrencyCode::EUR()->getIndex() || UserRole::isAdmin(auth()->user()->role)) {
            $euro = true;
        }

        $projectEmployees = $projectEmployees->groupBy('employee_id');
        $projectEmployees = $projectEmployees->map(function ($projectEmployee) use ($employeeService, $euro) {
            $employee = $employeeService->findBorrowedEmployee($projectEmployee[0]->employee_id);
            $employee->hours = $projectEmployee->sum('hours');
            $employee->employee_cost = $euro ? $projectEmployee->sum('employee_cost') * (1/$projectEmployee[0]->currency_rate_employee)
              : $projectEmployee->sum('employee_cost') * (1/$projectEmployee[0]->currency_rate_employee) * $projectEmployee[0]->currency_rate_dollar;
            $employee->project_id = $this->id;
            $employee->euro = $euro;

            return $employee;
        });

        if ($this->purchase_order_project) {
            $resource_id = $this->purchaseOrders[0]['resource_id'];
            $resourceService = App::make(ResourceService::class);
            $resource = $resourceService->findBorrowedResource($resource_id);
            if (!$resource) {
                $employeeService = App::make(EmployeeService::class);
                $resource = $employeeService->findBorrowedEmployee($resource_id);
            }
        }

        $salesPersons = $this->salesPersons;
        $leadGens = $this->leadGens;

        return
          [
              'id' => $this->id,
              'name' => $this->name ?? null,
              'contact' => $this->contact ? $this->contact->name : null,
              'contact_id' => $this->contact_id ?? null,
              'project_manager' => $this->projectManager ? $this->projectManager->name : null,
              'project_manager_id' => $this->project_manager_id ?? null,
              'sales_person' => getNames($salesPersons) ?? null,
              'sales_person_id' => $salesPersons->pluck('id') ?? null,
              'second_sales_person' => getNames($leadGens) ?? null,
              'second_sales_person_id' => $leadGens->pluck('id') ?? null,
              'active_quote' => $this->quotes->whereIn('status', [QuoteStatus::draft()->getIndex(), QuoteStatus::sent()->getIndex(), QuoteStatus::ordered()->getIndex()])->count() == 0 ? false : true,
              'created_at' => $this->created_at,
              'updated_at' => $this->updated_at ?? null,
              'price_modifiers_calculation_logic'=> $this->price_modifiers_calculation_logic ?? 1,
              'exceeds_threshold' => $exceedsThreshold,
              'customer' => [
                  'id' => $this->contact ? $this->contact->customer->id : null,
                  'name' => $this->contact ? $this->contact->customer->name : null,
                  'country' => $this->contact ? $this->contact->customer->billing_address->country : null,
                  'contacts' => $this->contact ? ProjectContactsResource::collection(Contact::where('customer_id', $this->contact->customer->id)->orderBy('last_name')->get()) : null,
                  'non_vat_liable' => $this->contact ? $this->contact->customer->non_vat_liable : null,
                  'legacy_customer' => $this->contact ? $company->legacyCustomers()->where('customer_id', $this->contact->customer->id)->exists() : false,
                  'payment_due_date' => $this->contact ? $this->contact->customer->payment_due_date : null,
              ],
              'order' => ProjectOrdersResource::make($this->order),
              'employees' => [
                  'rows' => [
                      'data' => ProjectEmployeesResource::collection($projectEmployees->flatten()),
                      'count' => $projectEmployees->count()
                  ],
              ],
              'quotes' => [
                  'rows' => [
                      'data' => ProjectQuotesResource::collection($this->quotes->where('shadow', false)),
                      'count' => $this->quotes->where('shadow', false)->count()
                  ] ,
              ],
              'purchase_orders' => [
                  'rows' => [
                      'data' => ProjectPurchaseOrdersResource::collection($this->purchaseOrders),
                      'count' => $this->purchaseOrders->count()
                  ],
              ],
              'project_commissions' => [
                    'rows' => [
                        'data' => ProjectSalesPersonCommissionSummaryResource::collection($projectCommissions),
                        'count' => count($projectCommissions)
                    ],
                ],
              'purchase_order_project' => $this->purchase_order_project,
              $this->mergeWhen($this->purchase_order_project, [
                  'resource'  => [
                      'id'               => $resource ? $resource->id : null,
                      'name'             => $resource ? $resource->name : null,
                      'country'          => $resource ? $resource->country : null,
                      'currency'         => $resource ? $resource->default_currency : null,
                      'non_vat_liable'   => $resource ? $resource->non_vat_liable : null,
                  ]
              ]),
          ];
    }
}
