<?php

namespace App\Services\Export;

use App\Enums\EmployeeType;
use App\Enums\CurrencyCode;
use App\Http\Resources\Export\EmployeeResource;
use App\Models\Employee;
use Illuminate\Support\Facades\Response;
use Phpdocx\Create\CreateDocxFromTemplate;


class EmployeeExporter extends BaseExport
{
    private $variablesArray = [
            'VAR_NAME' => 'Name of the employee',
            'VAR_FIRST_NAME' => 'First name of the employee',
            'VAR_LAST_NAME' => 'Last name of the employee',
            'VAR_EMAIL' => 'Email address of the employee',
            'VAR_CURRENCY' => 'Default currency where this employee is paid in',
            'VAR_PHONE_NUMBER' => 'Phone number of the employee',
            'VAR_ADDRESSLINE_1' => 'Street and street number of the employee',
            'VAR_ADDRESSLINE_2' => 'Second address line of the employee',
            'VAR_CITY' => 'City of the employee',
            'VAR_REGION' => 'Region of the employee',
            'VAR_POSTAL_CODE' => 'Postal code of the employee',
            'VAR_COUNTRY' => 'Country of the employee',
            'VAR_DATE' => 'Date the document is generated',
            'VAR_LEGAL_NAME' => 'The name of the legal entity',
            'VAR_LEGAL_VAT' => 'The vat-number of the legal entity',
            'VAR_LEGAL_ADDRESSLINE_1' => 'Street and street number of the legal entity',
            'VAR_LEGAL_ADDRESSLINE_2' => 'Second address line of the legal entity',
            'VAR_LEGAL_CITY' => 'City of the legal entity',
            'VAR_LEGAL_REGION' => 'Region of the legal entity',
            'VAR_LEGAL_POSTAL_CODE' => 'Postal code of the legal entity',
            'VAR_LEGAL_COUNTRY' => 'Country of the legal entity',
            'VAR_BANK_ADDRESSLINE_1' => 'Street and street number of the bank of the legal entity',
            'VAR_BANK_ADDRESSLINE_2' => 'Second address line of the bank of the legal entity',
            'VAR_BANK_CITY' => 'City of the bank of the legal entity',
            'VAR_BANK_REGION' => 'Region of the bank of the legal entity',
            'VAR_BANK_POSTAL_CODE' => 'Postal code of the bank of the legal entity',
            'VAR_BANK_COUNTRY' => 'Country of the bank of the legal entity',
            'VAR_BANK_NAME' => 'Name of the bank of the legal entity',
            'BLOCK_EU' => 'Place the swift and bic of the european bank between this block',
            'VAR_BANK_IBAN' => 'IBAN of the legal entity',
            'VAR_BANK_BIC' => 'BIC of the legal entity',
            'BLOCK_US' => 'Place the Account number and routing number of the american bank between this block',
            'VAR_BANK_ACCOUNT_NR' => 'Account number of the legal entity',
            'VAR_BANK_ROUTING_NR' => 'Routing number of the legal entity',
    ];

    protected array $variables;

    public function __construct()
    {
        $this->variables = [
            'document' => $this->variablesArray,
            'headers' => $this->variablesArray,
            'footers' => $this->variablesArray,
        ];
    }

    public function export(Employee $employee, $type, $format)
    {
        if (EmployeeType::isContractor($employee->type)) {
            $type = 'contractor';
        }
        $docx = new CreateDocxFromTemplate($employee->getTemplatePath(null)[$type]);
        $docx->processTemplate($this->getVariables());

        if ($employee->default_currency == CurrencyCode::USD()->getIndex()) {
            $docx->deleteTemplateBlock('EU');
            $docx->removeTemplateVariable('VAR_BANK_IBAN', 'block', 'footer');
            $docx->removeTemplateVariable('VAR_BANK_BIC', 'block', 'footer');
        } else {
            $docx->deleteTemplateBlock('US');
            $docx->removeTemplateVariable('VAR_BANK_ACCOUNT_NR', 'block', 'footer');
            $docx->removeTemplateVariable('VAR_BANK_ROUTING_NR', 'block', 'footer');
        }

        $docx->replaceVariableByText(EmployeeResource::make($employee)->resolve(), ['target' => 'document']);
        $docx->replaceVariableByText(EmployeeResource::make($employee)->resolve(), ['target' => 'header']);
        $docx->replaceVariableByText(EmployeeResource::make($employee)->resolve(), ['target' => 'footer']);
        $docx->clearBlocks();
        $docx->createDocx($employee->getExportPath());
        $media = $employee->addMedia($employee->getExportPath())->toMediaCollection($employee->getExportMediaCollection());

        if ($format === 'docx') {
            return response()->download($media->getPath(), $type.'.docx', ['Content-Type' => 'application/docx'], 'inline')->deleteFileAfterSend(true);
        }

        $docx->transformDocument($media->getPath(), $this->getPdfPath($media->getPath()));
        $response =  Response::make(file_get_contents($this->getPdfPath($media->getPath())), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$type.'.pdf'.'"'
        ]);
        $media->delete();
        return $response;
    }
}
