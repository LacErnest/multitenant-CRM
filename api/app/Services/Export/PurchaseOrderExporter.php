<?php

namespace App\Services\Export;

use App\Enums\CurrencyCode;
use App\Http\Resources\Export\ItemModifierResource;
use App\Http\Resources\Export\ItemResource;
use App\Http\Resources\Export\ModifierResource;
use App\Http\Resources\Export\PurchaseOrderResource;
use App\Models\PriceModifier;
use App\Services\Export\Interfaces\CanBeExported;
use Illuminate\Support\Facades\Response;
use Phpdocx\Create\CreateDocxFromTemplate;

class PurchaseOrderExporter extends BaseExport
{
    private $variablesArray = [
            'VAR_DATE' => 'Creation date that is shown on the purchase order',
            'VAR_DELIVERY_DATE' => 'Expected delivery date for the services listed in this purchase order',
            'VAR_NUMBER' => 'Purchase order number (follows the defined purchase order format)',
            'VAR_REFERENCE' => 'Reference that is shown on the purchase order for the customer his records',
            'VAR_CURRENCY' => 'Currency that is used in this purchase order',
            'VAR_TOTAL_PRICE' => 'Total price (including VAT) of the purchase order',
            'VAR_TOTAL_VAT' => 'Total VAT of the purchase order',
            'VAR_WITHOUT_VAT' => 'Total price (excluding VAT) of the purchase order',
            'VAR_TOTAL_WITHOUT_MODS' => 'Total price without discounts and markups',
            'VAR_PM_NAME' => 'Name of the project manager',
            'VAR_PM_FIRST_NAME' => 'First name of the project manager',
            'VAR_PM_LAST_NAME' => 'Last name of the project manager',
            'VAR_PM_EMAIL' => 'Email address of the project manager',
            'VAR_PM_PHONE_NUMBER' => 'Phone number of the project manager',
            'VAR_R_NAME' => 'Name of the resource',
            'VAR_R_FIRST_NAME' => 'First name of the resource',
            'VAR_R_LAST_NAME' => 'Last name of the resource',
            'VAR_R_EMAIL' => 'Email address of the resource',
            'VAR_R_TAX_NUMBER' => 'Tax number of the resource',
            'VAR_R_PHONE_NUMBER' => 'Phone number of the resource',
            'VAR_R_ADDRESSLINE_1' => 'Street and street number of the resource',
            'VAR_R_ADDRESSLINE_2' => 'Second address line of the resource',
            'VAR_R_CITY' => 'City of the resource',
            'VAR_R_REGION' => 'Region of the resource',
            'VAR_R_POSTAL_CODE' => 'Postal code of the resource',
            'VAR_R_COUNTRY' => 'Country of the resource',
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
            'BLOCK_MOD' => 'Place purchase order modifiers between these variables',
            'VAR_M_TYPE' => 'Purchase order modifier type (discount / charge) (use within a table row)',
            'VAR_M_DESCRIPTION' => 'Purchase order modifier description (use within a table row)',
            'VAR_M_QUANTITY' => 'Purchase order modifier quantity (use within a table row)',
            'VAR_M_QUANTITY_TYPE' => 'Quote modifier quantity type (fixed / percentage) (use within a table row)',
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

    public function export(CanBeExported $purchaseOrder, $template_id, $type)
    {
        //Get template
        $docx = new CreateDocxFromTemplate($purchaseOrder->getTemplatePath($template_id));
        $docx->processTemplate($this->getVariables());

        // Remove price modifiers subtoal table row from template when old calculation logic is set
        if (empty($purchaseOrder->project->price_modifiers_calculation_logic)) {
            $referenceNode = array(
              'customQuery' => '//w:tbl/w:tr[contains(.,"SUBTOTAL AFFECTED BY PRICE MODIFIERS")]',
            );
            $docx->removeWordContent($referenceNode);
        }

        if ($purchaseOrder->currency_code == CurrencyCode::USD()->getIndex()) {
            $docx->deleteTemplateBlock('EU');
            $docx->removeTemplateVariable('VAR_BANK_IBAN', 'block', 'footer');
            $docx->removeTemplateVariable('VAR_BANK_BIC', 'block', 'footer');
        } elseif ($purchaseOrder->currency_code == CurrencyCode::USDC()->getIndex()) {
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

        $docx->replaceVariableByText(PurchaseOrderResource::make($purchaseOrder)->resolve(), ['target' => 'document']);
        $docx->replaceVariableByText(PurchaseOrderResource::make($purchaseOrder)->resolve(), ['target' => 'header']);
        $docx->replaceVariableByText(PurchaseOrderResource::make($purchaseOrder)->resolve(), ['target' => 'footer']);

        //Add modifiers of purchase order to template
        if ($purchaseOrder->penalty && !$purchaseOrder->priceModifiers()->where('description', 'like', 'Penalty%')->exists()) {
            $penalty = new PriceModifier();
            $penalty->description = 'Penalty (' . $purchaseOrder->reason_of_penalty . ')';
            $penalty->type = 0;
            $penalty->quantity = $purchaseOrder->penalty;
            $penalty->quantity_type = $purchaseOrder->penalty_type ?? 0;
            $penalty->entity_id = $purchaseOrder->id;
            $purchaseOrder->priceModifiers()->save($penalty);
        }
        $modifiers = getPriceModifiers($purchaseOrder);
        $orderedPriceModifier = orderPriceModifiers($purchaseOrder, $modifiers);
        $modifiers = ModifierResource::collection($orderedPriceModifier)->resolve();
        if (!empty($modifiers)) {
            $docx->replaceTableVariable($modifiers, ['firstMatch' => true]);
        } else {
            $docx->deleteTemplateBlock('MOD');
            $docx->removeTemplateVariable('VAR_M_QUANTITY', 'inline');
            $docx->removeTemplateVariable('VAR_M_DESCRIPTION', 'inline');
        }
        $docx->clearBlocks();

        //Add items of purchase order to template
        $purchaseItems = $purchaseOrder->items()->with('priceModifiers')->orderBy('order')->get();
        $items = ItemResource::collection($purchaseItems)->resolve();

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
            $docx->replaceTableVariable($items, ['firstMatch' => true, 'parseLineBreaks' => true]);
        } else {
            $docx->removeTemplateVariable('VAR_I_M_QUANTITY', 'inline');
            $docx->removeTemplateVariable('VAR_I_M_DESCRIPTION', 'inline');
            $docx->removeTemplateVariable('VAR_I_DESCRIPTION', 'inline');
            $docx->removeTemplateVariable('VAR_I_QUANTITY', 'inline');
            $docx->removeTemplateVariable('VAR_I_UNIT', 'inline');
            $docx->removeTemplateVariable('VAR_I_PRICE', 'inline');
            $docx->removeTemplateVariable('VAR_I_TOTAL_PRICE', 'inline');
        }

        //Create docx file
        $docx->createDocx($purchaseOrder->getExportPath());
        $media = $purchaseOrder->addMedia($purchaseOrder->getExportPath())->toMediaCollection($purchaseOrder->getExportMediaCollection());

        //Download docx file
        if ($type == 'docx') {
            return response()->download($media->getPath(), 'purchase_order.docx', ['Content-Type' => 'application/docx'], 'inline')->deleteFileAfterSend(true);
        }

        //Show pdf
        $docx->transformDocument($media->getPath(), $this->getPdfPath($media->getPath()));
        $response =  Response::make(file_get_contents($this->getPdfPath($media->getPath())), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="purchase_order.pdf'.'"'
        ]);
        $media->delete();
        return $response;
    }
}
