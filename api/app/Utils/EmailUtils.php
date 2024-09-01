<?php

namespace App\Utils;

use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\VatStatus;
use App\Models\Company;
use App\Models\DesignTemplate;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\DesignTemplateService;
use App\Services\EmailTemplateService;
use BadFunctionCallException;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EmailUtils
{
    /**
     * @var \App\Services\DesignTemplateService
     */
    private $designTemplateService;

    private const MONEY_VALUE_VARIABLES = [
        'total_price', 'total_vat', 'manual_price', 'manual_vat', 'total_amount_paid',
        'total_price_usd', 'total_vat_usd'
    ];
    private const DATE_VARIABLES = ['date', 'due_date', 'pay_date', 'close_date', 'submitted_date', 'updated_at', 'created_at'];

    private const TIMESTAMP_DATE_VARIABLES = ['submitted_date', 'updated_at', 'created_at'];

    private static $VARIABLES = [];
    /**
     * Create a new message instance.
     *
     */
    public function __construct()
    {
        $this->designTemplateService = app(DesignTemplateService::class);
    }

    public function buildVariables($entity, $defaultVariable = []): array
    {
        $invoiceTypeLabel = InvoiceType::make($entity->type)->getValue();
        $vatStatusLabel = VatStatus::make($entity->vat_status)->getValue();
        $invoiceStatusLabel = InvoiceStatus::make($entity->status)->getValue();
        $currencyRateCustomer = $entity->currency_rate_customer;
        $currencyRateCompany = $entity->currency_rate_company;
        if ($entity->currency_code == CurrencyCode::EUR()->getIndex()) {
            $currencyRateToEUR = safeDivide(1, $currencyRateCustomer);
        } else {
            $currencyRateToEUR = $currencyRateCompany * safeDivide(1, $currencyRateCustomer);
        }
        $totalPaidAmount = ceiling($entity->payments ? $entity->payments->sum('pay_amount') : 0, 2);
        $totalVat = $entity->total_vat ?? 0;


        $variables = array_merge(
            $entity->toArray(),
            [
                'project_name' => $entity->project->name,
                'see_invoice' => $this->getUrlLink($defaultVariable['url'] ?? ''),
                'total_price_customer_cur' => formatMoneyValue(ceiling(get_total_price(get_class($entity), $entity->id), 2), CurrencyCode::make($entity->currency_code)->getValue()),
                'total_vat_customer_cur' => formatMoneyValue($totalVat * $currencyRateCustomer, CurrencyCode::make($entity->currency_code)->getValue()),
                'total_paid_customer_cur' => formatMoneyValue(ceiling($totalPaidAmount * $currencyRateCustomer, 2), CurrencyCode::make($entity->currency_code)->getValue()),
            ],
            $defaultVariable
        );

        // Append currency code to money value variables
        foreach (self::MONEY_VALUE_VARIABLES as $variable) {
            if (!isset($variables[$variable])) {
                continue;
            }

            $variableValue = $variables[$variable] ?? 0;

            if (strpos($variable, '_usd') !== false) {
                $variables[$variable] = formatMoneyValue($variableValue, CurrencyCode::USD()->getValue());
                continue;
            }

            if ($variable == 'total_paid_amount') {
                $variables['total_paid_amount'] = formatMoneyValue($totalPaidAmount * $currencyRateToEUR, CurrencyCode::EUR()->getValue());
                continue;
            }

            $variables[$variable] = formatMoneyValue($variableValue, CurrencyCode::EUR()->getValue());
        }

        $variables['vat_percentage'] = !empty($variables['vat_percentage']) ? sprintf('%s %s', $variables['vat_percentage'], '%') : 'N/A';
        $variables['type'] = !empty($variables['type']) ? $invoiceTypeLabel : 'N/A';
        $variables['status'] = !empty($variables['status']) ? $invoiceStatusLabel : 'N/A';
        $variables['vat_status'] = !empty($variables['vat_status']) ? $vatStatusLabel : 'N/A';

        $dateVariables = ['date', 'due_date', 'pay_date', 'close_date', 'submitted_date', 'updated_at', 'created_at'];
        foreach ($dateVariables as $variable) {
            $date = $variables[$variable] ? new DateTime($variables[$variable]) : null;
            if (!($date === null)) {
                if ($variable == 'submitted_date' || $variable == 'created_at' || $variable == 'updated_at') {
                    $variables[$variable] = $date->format('Y-m-d H:i:s');
                } else {
                    $variables[$variable] = $date->format('Y-m-d');
                }
            }
            $date = new DateTime($variables[$variable]);
            $format = in_array($variable, self::TIMESTAMP_DATE_VARIABLES) ? 'Y-m-d H:i:s' : 'Y-m-d';
            $variables[$variable] = $date->format($format);
        }

        if (!empty($variables['created_by'])) {
            $createdBy = User::where('id', $variables['created_by'])->first();
            $variables['created_by'] = $createdBy->first_name . ' ' . $createdBy->last_name;
        }
        return $variables;
    }

    private function getUrlLink($url): string
    {
        return '<a target="_blank" ' .
            'rel="noopener noreferrer" href="' . $url . '" ' .
            'class="button button-primary" ' .
            'style="box-sizing: border-box;' .
            'font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif, \'Apple Color Emoji\', \'Segoe UI Emoji\', \'Segoe UI Symbol\';' .
            'position: relative;' .
            '-webkit-text-size-adjust: none;' .
            'border-radius: 4px;' .
            'color: #fff;' .
            'display: inline-block;' .
            'overflow: hidden;' .
            'text-decoration: none;' .
            'background-color: #2d3748;' .
            'border-bottom: 8px solid #2d3748;' .
            'border-left: 18px solid #2d3748;' .
            'border-right: 18px solid #2d3748;' .
            'border-top: 8px solid #2d3748;">See Invoice</a>';
    }

    /**
     * @param string $template
     * @param array $variables
     * @return string
     */
    private function replaceTemplateVariables(string $template, array $variables = []): string
    {
        return preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($variables) {
            $variableName = trim($matches[1]);
            if ($variables[$variableName] ?? '' == 'see_invoice') {
                return html_entity_decode($variables[$variableName]);
            }
            return $variables[$variableName] ?? '';
        }, $template) ?? '';
    }

    public function loadVariables(Company $company, $entity, $variables = [], $force = true)
    {
        if ($force || !empty(static::$VARIABLES)) {
            $my = new static();
            $url = config('app.front_url') . '/' . $company->id . '/projects/' . $entity->project_id . '/invoices/' . $entity->id . '/edit';
            try {
                static::$VARIABLES = $my->buildVariables($entity, array_merge(['url' => $url], $variables));
            } catch (Exception $e) {
                Log::error($e->getMessage());
                throw new BadFunctionCallException('Error while building variables: ' . $e->getMessage());
            }
        }
        return $this;
    }

    public function translateVariable($string)
    {
        if ((empty(static::$VARIABLES))) {
            return $string;
        }
        return $this->replaceTemplateVariables($string, static::$VARIABLES);
    }

    public static function clearVariables()
    {
        static::$VARIABLES = [];
    }

    public function buildEmailTemplate(Company $company, $entity, DesignTemplate $designTemplate, $variables = [])
    {

        $this->loadVariables($company, $entity, $variables);
        $templateWordPath = $this->designTemplateService->getDesignTemplateHtmlPath($designTemplate);
        $templateBody = file_get_contents($templateWordPath);
        $templateBody = ImageUtils::replaceImageUris($templateBody, false);
        return $this->replaceTemplateVariables($templateBody, static::$VARIABLES);
    }
    /**
     * Get email template if available or return default template
     */
    public static function getEntityEmailTemplate(Model $entity)
    {
        $emailTemplate = $entity->emailTemplate;
        if (empty($emailTemplate)) {
            $emailTemplateService = app(EmailTemplateService::class);
            return $emailTemplateService->getDefaultEmailTemplate();
        }
        return $emailTemplate;
    }

    /**
     * Build cc emails
     *
     * @param array $arrays
     * @param array | string | null $emails
     * @return void
     */
    public static function pushEmail(&$array, $emails)
    {
        if (!empty($emails) && !is_array($emails)) {
            array_push($array, $emails);
        }
        if (!empty($emails) && is_array($emails)) {
            foreach ($emails as $email) {
                if (!empty($email)) {
                    array_push($array, $email);
                }
            }
        }
    }
}
