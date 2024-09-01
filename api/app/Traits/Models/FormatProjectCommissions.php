<?php

namespace App\Traits\Models;

use App\Enums\CommissionModel;
use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\SalesCommission;
use App\Models\SalesCommissionPercentage;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Support\Facades\App;

trait FormatProjectCommissions
{

    public function projectCommissions($salesPersons, $project, $company)
    {
        $projectCommissions = [];
        $invoices = $project->invoices()->where([
            ['type', InvoiceType::accrec()->getIndex()],
            ['status', InvoiceStatus::paid()->getIndex()]
          ])->orderByDesc('updated_at')->get();
        foreach ($salesPersons as $key => $salesPerson) {
            if (!$salesPerson) {
                continue;
            }
            $salesArray = [];
            $salesPerson = User::find($salesPerson->getAttribute('id'));
            if (!$salesPerson) {
                continue;
            }
            $linkedUsers = User::where('email', $salesPerson->email)->orderBy('created_at', 'ASC')->get();
            $salesPerson = $linkedUsers->where('primary_account', true)->first() ?? $salesPerson;
            $projectCommissions[$key]['sales_person_id'] = $salesPerson->id;
            $projectCommissions[$key]['sales_person'] = $salesPerson->name;
            $salesRecord = $salesPerson->salesCommissions->sortByDesc('created_at')->first();
            $commissionModel = $salesRecord->commission_model ?? CommissionModel::default()->getIndex();
            if (CommissionModel::isLead_generation($commissionModel)) {
                $baseCommission = $salesRecord->commission ?? SalesCommission::DEFAULT_LEAD_COMMISSION;
            } elseif (CommissionModel::isLead_generationB($commissionModel)) {
                $baseCommission = $salesRecord->commission ?? SalesCommission::DEFAULT_LEAD_COMMISSION_B;
            } elseif (CommissionModel::isDefault($commissionModel)) {
                $baseCommission = $salesRecord->commission ?? SalesCommission::DEFAULT_COMMISSION;
            } elseif (CommissionModel::isSales_support($commissionModel)) {
                $baseCommission = $this->company->sales_support_commission;
            } elseif (CommissionModel::isCustom_modelA($commissionModel)) {
                $baseCommission = SalesCommission::DEFAULT_SECOND_SALE;
            }
            $projectCommissions[$key]['base_commission'] = $baseCommission;
            $projectCommissions[$key]['current_commission_model'] = CommissionModel::make($commissionModel)->getValue();
            $percentages =  SalesCommissionPercentage::select('order_id', 'invoice_id', 'sales_person_id', 'commission_percentage', 'type', 'id')
            ->whereIn('invoice_id', $invoices->pluck('id')->toArray())
            ->where('sales_person_id', $salesPerson->id)
            ->get();
            $projectCommissions[$key]['commissions'] = [];
            $projectCommissions[$key]['total_commission'] = 0;
            foreach ($percentages as $percentage) {
                $invoice = Invoice::where('id', $percentage->invoice_id)->first();
                if ($company->currency_code == CurrencyCode::EUR()->getIndex()) {
                    $currencyRateToEUR = safeDivide(1, $invoice->currency_rate_customer);
                } else {
                    $currencyRateToEUR = $invoice->currency_rate_company * safeDivide(1, $invoice->currency_rate_customer);
                }
                $invoiceData = [
                    'id' => $invoice->id,
                    'status' => $invoice->status,
                    'order_status' => $invoice->order->status,
                    'number' => $invoice->number,
                    'order_number' => $invoice->order->number,
                    'total_price' => $invoice->total_price,
                    'order_id' => $invoice->order->id,
                    'project_id' => $invoice->project->id,
                ];
                $projectCommissions[$key]['commissions'][] = formatCommission($invoiceData, calculateGrossMargin($invoice), $invoice->payments->sum('pay_amount') * $currencyRateToEUR, $percentage, $percentage->commission_percentage, $invoice->total_price);
            }
            foreach ($projectCommissions[$key]['commissions'] as $commission) {
                $projectCommissions[$key]['total_commission'] += $commission['commission'];
            }
            $projectCommissions[$key]['nb_commission'] = count($projectCommissions[$key]['commissions']);
        }
        return $projectCommissions;
    }
}
