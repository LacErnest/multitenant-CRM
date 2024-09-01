<?php

namespace App\Services\Export;

use App\Http\Resources\Export\CustomerResource;
use App\Services\Export\Interfaces\CanBeExported;
use Illuminate\Support\Facades\Response;
use Phpdocx\Create\CreateDocxFromTemplate;


class CustomerExporter extends BaseExport
{
    private $variablesArray = [
            'VAR_NAME' => 'Name of the customer',
            'VAR_TAX_NUMBER' => 'Tax number of the customer',
            'VAR_EMAIL' => 'Email address of the customer',
            'VAR_WEBSITE' => 'Website of the customer',
            'VAR_CURRENCY' => 'Default currency used when creating a quote or invoice for this customer',
            'VAR_PHONE_NUMBER' => 'Phone number of the customer',
            'VAR_ADDRESSLINE_1' => 'Street and street number of the customer',
            'VAR_ADDRESSLINE_2' => 'Second address line of the customer',
            'VAR_CITY' => 'City of the customer',
            'VAR_REGION' => 'Region of the customer',
            'VAR_POSTAL_CODE' => 'Postal code of the customer',
            'VAR_COUNTRY' => 'Country of the customer',
            'VAR_DATE' => 'Date the document is generated',
            'VAR_S_FIRST_NAME' => 'First name of the sales person',
            'VAR_S_LAST_NAME' => 'Last name of the sales person',
            'VAR_S_EMAIL' => 'Email address of the sales person',
            'VAR_CC_FIRST'              => 'First name of the customer contact',
            'VAR_CC_LAST'               => 'Last name of the customer contact',
            'VAR_CC_EMAIL'              => 'Email of the customer contact',
            'VAR_CC_PHONE'              => 'Phone number of the customer contact',
            'VAR_CC_TITLE'              => 'Title of the customer contact',
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

    public function export(CanBeExported $customer, $type, $format, string $legalEntityId)
    {
        $docx = new CreateDocxFromTemplate($customer->getCustomerTemplatePath($legalEntityId));
        $docx->processTemplate($this->getVariables());

        $docx->replaceVariableByText(CustomerResource::make($customer)->resolve(), ['target' => 'document']);
        $docx->replaceVariableByText(CustomerResource::make($customer)->resolve(), ['target' => 'header']);
        $docx->replaceVariableByText(CustomerResource::make($customer)->resolve(), ['target' => 'footer']);
        $docx->createDocx($customer->getExportPath());
        $media = $customer->addMedia($customer->getExportPath())->toMediaCollection($customer->getExportMediaCollection());

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
