<?php

namespace App\Services\Export;

use App\Enums\CurrencyCode;
use App\Http\Resources\Export\ResourceResource;
use App\Models\Resource;
use Illuminate\Support\Facades\Response;
use Phpdocx\Create\CreateDocxFromTemplate;


class ResourceExporter extends BaseExport
{
    private $variablesArray = [
            'VAR_NAME' => 'Name of the resource',
            'VAR_FIRST_NAME' => 'First name of the resource',
            'VAR_LAST_NAME' => 'Last name of the resource',
            'VAR_EMAIL' => 'Email address of the resource',
            'VAR_TAX_NUMBER' => 'Tax number of the resource',
            'VAR_CURRENCY' => 'Default currency used when creating a purchase order for this resource',
            'VAR_PHONE_NUMBER' => 'Phone number of the resource',
            'VAR_ADDRESSLINE_1' => 'Street and street number of the resource',
            'VAR_ADDRESSLINE_2' => 'Second address line of the resource',
            'VAR_CITY' => 'City of the resource',
            'VAR_REGION' => 'Region of the resource',
            'VAR_POSTAL_CODE' => 'Postal code of the resource',
            'VAR_COUNTRY' => 'Country of the resource',
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

    public function export(Resource $resource, $type, $format)
    {
        $docx = new CreateDocxFromTemplate($resource->getTemplatePath(null)[$type]);
        $docx->processTemplate($this->getVariables());

        if ($resource->default_currency == CurrencyCode::USD()->getIndex()) {
            $docx->deleteTemplateBlock('EU');
            $docx->removeTemplateVariable('VAR_BANK_IBAN', 'block', 'footer');
            $docx->removeTemplateVariable('VAR_BANK_BIC', 'block', 'footer');
        } else {
            $docx->deleteTemplateBlock('US');
            $docx->removeTemplateVariable('VAR_BANK_ACCOUNT_NR', 'block', 'footer');
            $docx->removeTemplateVariable('VAR_BANK_ROUTING_NR', 'block', 'footer');
        }

        $docx->replaceVariableByText(ResourceResource::make($resource)->resolve(), ['target' => 'document']);
        $docx->replaceVariableByText(ResourceResource::make($resource)->resolve(), ['target' => 'header']);
        $docx->replaceVariableByText(ResourceResource::make($resource)->resolve(), ['target' => 'footer']);
        $docx->clearBlocks();
        $docx->createDocx($resource->getExportPath());
        $media = $resource->addMedia($resource->getExportPath())->toMediaCollection($resource->getExportMediaCollection());

        if ($format == 'docx') {
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
