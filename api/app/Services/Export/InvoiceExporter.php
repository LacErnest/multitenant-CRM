<?php

namespace App\Services\Export;

use App\Enums\CurrencyCode;
use App\Enums\EntityModifierDescription;
use App\Http\Resources\Export\InvoiceResource;
use App\Http\Resources\Export\ItemModifierResource;
use App\Http\Resources\Export\ItemResource;
use App\Http\Resources\Export\ModifierResource;
use App\Services\Export\Interfaces\CanBeExported;
use Illuminate\Support\Facades\Response;
use Phpdocx\Create\CreateDocxFromTemplate;

class InvoiceExporter extends BaseExport
{
    private $variablesArray = [
            'VAR_DATE' => 'Creation date that is shown on the invoice',
            'VAR_DUE_DATE' => 'Date at which the invoice needs to be paid',
            'VAR_NUMBER' => 'Invoice number (follows the defined invoice format)',
            'VAR_REFERENCE' => 'Reference that is shown on the invoice for the customer his records',
            'VAR_CURRENCY' => 'Currency that is used in this invoice',
            'VAR_TOTAL_PRICE' => 'Total price (including VAT) of the invoice',
            'VAR_TOTAL_VAT' => 'Total VAT of the invoice',
            'VAR_WITHOUT_VAT' => 'Total price (excluding VAT) of the invoice',
            'VAR_DOWN_PAYMENT' => 'The amount to be paid as down payment',
            'VAR_TOTAL_WITHOUT_MODS' => 'Total price without discounts and markups',
            'VAR_PM_NAME' => 'Name of the project manager',
            'VAR_PM_FIRST_NAME' => 'First name of the project manager',
            'VAR_PM_LAST_NAME' => 'Last name of the project manager',
            'VAR_PM_EMAIL' => 'Email address of the project manager',
            'VAR_PM_PHONE_NUMBER' => 'Phone number of the project manager',
            'VAR_C_NAME' => 'Name of the customer',
            'VAR_C_EMAIL' => 'Email of the customer',
            'VAR_C_TAX_NUMBER' => 'Tax number of the customer',
            'VAR_C_DEFAULT_CURRENCY' => 'Default currency of the customer',
            'VAR_C_WEBSITE' => 'Website of the customer',
            'VAR_C_PHONE_NUMBER' => 'Phone number of the customer',
            'VAR_C_ADDRESSLINE_1' => 'Street and street number of the customer',
            'VAR_C_ADDRESSLINE_2' => 'Second address line of the customer',
            'VAR_C_CITY' => 'City of the customer',
            'VAR_C_REGION' => 'Region of the customer',
            'VAR_C_POSTAL_CODE' => 'Postal code of the customer',
            'VAR_C_COUNTRY' => 'Country if the customer',
            'VAR_I_DESCRIPTION' => 'Item description (use within a table row)',
            'VAR_I_QUANTITY' => 'Item quantity (use within a table row)',
            'VAR_I_PRICE' => 'Item unit price (use within a table row)',
            'VAR_I_TOTAL_PRICE' => 'Item total price (use within a table row)',
            'VAR_I_SUBTOTAL' => 'Item subtotal (use within a table row)',
            'VAR_I_UNIT' => 'Item unit (use within a table row)',
            'VAR_I_M_TYPE' => 'Item modifier type (discount / charge) (use within a table row)',
            'VAR_I_M_DESCRIPTION' => 'Item modifier description (use within a table row)',
            'VAR_I_M_QUANTITY' => 'Item modifier quantity (use within a table row)',
            'VAR_I_M_QUANTITY_TYPE' => 'Item modifier quantity type (fixed / percentage) (use within a table row)',
            'BLOCK_MOD' => 'Place invoice modifiers between these variables',
            'VAR_M_TYPE' => 'Invoice modifier type (discount / charge) (use within a table row)',
            'VAR_M_DESCRIPTION' => 'Invoice modifier description (use within a table row)',
            'VAR_M_QUANTITY' => 'Invoice modifier quantity (use within a table row)',
            'VAR_M_QUANTITY_TYPE' => 'Invoice modifier quantity type (fixed / percentage) (use within a table row)',
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
            'VAR_U_NAME'          => 'Full name of the logged in user',
            'VAR_U_FIRST_NAME'    => 'First name of the logged in user',
            'VAR_U_LAST_NAME'     => 'Last name of the logged in user',
            'VAR_U_EMAIL'         => 'Email of the logged in user',
            'VAR_TAX_RATE'         => 'Tax rate used on the entity',
            'VAR_CC_FIRST'              => 'First name of the customer contact',
            'VAR_CC_LAST'               => 'Last name of the customer contact',
            'VAR_CC_EMAIL'              => 'Email of the customer contact',
            'VAR_CC_PHONE'              => 'Phone number of the customer contact',
            'VAR_CC_TITLE'              => 'Title of the customer contact',
            'VAR_USDC_TITLE'              => 'Please pay to our USDC Crypto Wallet addres :',
            'VAR_TRANS_FEE'            => 'Value of the transaction fee',
            'VAR_TRANS_FEE_LABEL'       => 'Label of the transaction fee',
            'VAR_USDC_WALLET_ADDRESS'   => 'USDC wallet address',
            'VAR_TOTAL_AFFECTED_BY_MODS' => 'Subtotal affected by price modifiers',
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

    public function export(CanBeExported $invoice, $template_id, $type)
    {
        //Get template
        $docx = new CreateDocxFromTemplate($invoice->getTemplatePath($template_id));
        $docx->processTemplate($this->getVariables());

        // Remove price modifiers subtoal table row from template when old calculation logic is set
        if (empty($invoice->project->price_modifiers_calculation_logic)) {
            $referenceNode = array(
              'customQuery' => '//w:tbl/w:tr[contains(.,"SUBTOTAL AFFECTED BY PRICE MODIFIERS")]',
            );
            $docx->removeWordContent($referenceNode);
        }

        if ($invoice->currency_code === CurrencyCode::USD()->getIndex()) {
            $docx->deleteTemplateBlock('EU');
            $docx->deleteTemplateBlock('USDC');
            $docx->removeTemplateVariable('VAR_BANK_IBAN', 'block', 'footer');
            $docx->removeTemplateVariable('VAR_BANK_BIC', 'block', 'footer');
        } elseif ($invoice->currency_code == CurrencyCode::USDC()->getIndex()) {
            $docx->deleteTemplateBlock('EU');
            $docx->removeTemplateVariable('VAR_BANK_IBAN', 'block', 'footer');
            $docx->removeTemplateVariable('VAR_BANK_BIC', 'block', 'footer');

            $docx->deleteTemplateBlock('US');
            $docx->removeTemplateVariable('VAR_BANK_ACCOUNT_NR', 'block', 'footer');
            $docx->removeTemplateVariable('VAR_BANK_ROUTING_NR', 'block', 'footer');
        } else {
            $docx->deleteTemplateBlock('US');
            $docx->removeTemplateVariable('VAR_BANK_ACCOUNT_NR', 'block', 'footer');
            $docx->removeTemplateVariable('VAR_BANK_ROUTING_NR', 'block', 'footer');
        }


        $docx->replaceVariableByText(InvoiceResource::make($invoice)->resolve(), ['target' => 'document']);
        $docx->replaceVariableByText(InvoiceResource::make($invoice)->resolve(), ['target' => 'header']);
        $docx->replaceVariableByText(InvoiceResource::make($invoice)->resolve(), ['target' => 'footer']);

        //Add modifiers of quote to template
        $modifiers = getPriceModifiers($invoice);
        $orderedPriceModifier = orderPriceModifiers($invoice, $modifiers);
        $modifiers = ModifierResource::collection($orderedPriceModifier)->resolve();

        if (!empty($modifiers)) {
            $docx->replaceTableVariable($modifiers, ['firstMatch' => true]);
        } else {
            $docx->deleteTemplateBlock('MOD');
            $docx->removeTemplateVariable('VAR_M_QUANTITY', 'inline');
            $docx->removeTemplateVariable('VAR_M_DESCRIPTION', 'inline');
        }
        $docx->clearBlocks();

        //Add items of invoice to template
        $invoiceItems = $invoice->items()->with('priceModifiers')->orderBy('order')->get();
        $items = ItemResource::collection($invoiceItems)->resolve();

        $items = array_map(function ($item) {
            $item['VAR_I_M_DESCRIPTION'] = '';
            $item['VAR_I_M_QUANTITY'] = '';
            if (!empty($item['VAR_price_modifier'])) {
                foreach ($item['VAR_price_modifier'] as $modifier) {
                    $item['VAR_I_M_DESCRIPTION'] .= $modifier['VAR_I_M_DESCRIPTION'] . "\n";
                    $item['VAR_I_M_QUANTITY'] .= $modifier['VAR_I_M_QUANTITY'] . "\n";
                }
            }
            unset($item['VAR_price_modifier']);
            return $item;
        }, $items);

        if (!empty($items)) {
            $docx->replaceTableVariable($items);
        }

        //Add modifiers of items to template
        foreach ($invoiceItems as $item) {
            $modifiers = $item->priceModifiers()->get();
            $modifiers = ItemModifierResource::collection($modifiers)->resolve();
            if (!empty($modifiers)) {
                $docx->replaceTableVariable($modifiers, ['firstMatch' => true]);
            }
        }

        //Create docx file
        $docx->createDocx($invoice->getExportPath());
        $media = $invoice->addMedia($invoice->getExportPath())->toMediaCollection($invoice->getExportMediaCollection());

        //Download docx file
        if ($type == 'docx') {
            return response()->download($media->getPath(), 'invoice.docx', ['Content-Type' => 'application/docx'], 'inline')->deleteFileAfterSend(true);
        }

        //Show pdf
        $docx->transformDocument($media->getPath(), $this->getPdfPath($media->getPath()));
        $response = Response::make(file_get_contents($this->getPdfPath($media->getPath())), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="invoice.pdf'.'"'
        ]);
        $media->delete();
        return $response;
    }
}
