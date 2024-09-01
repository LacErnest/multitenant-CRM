<?php

namespace App\Services\Export;

use App\Enums\CurrencyCode;
use App\Enums\EntityModifierDescription;
use App\Http\Resources\Export\ItemModifierResource;
use App\Http\Resources\Export\ItemResource;
use App\Http\Resources\Export\PurchaseOrderReportResource;
use App\Http\Resources\Export\ModifierResource;
use App\Http\Resources\Export\OrderResource;
use App\Http\Resources\Export\ProjectEmployeeReportResource;
use App\Services\Export\Interfaces\CanBeExported;
use App\Services\Export\Xlsx\OrderReportExport;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use Phpdocx\Create\CreateDocxFromTemplate;

class OrderExporter extends BaseExport
{
    private $variablesArray = [
        'VAR_DATE' => 'Creation date that is shown on the order',
        'VAR_DEADLINE' => 'Deadline on which the order needs to be delivered',
        'VAR_NUMBER' => 'Order number (follows the defined order format)',
        'VAR_REFERENCE' => 'Reference that is shown on the order for the customer his records',
        'VAR_CURRENCY' => 'Currency that is used in this order',
        'VAR_TOTAL_PRICE' => 'Total price (including VAT) of the order',
        'VAR_TOTAL_VAT' => 'Total VAT of the order',
        'VAR_WITHOUT_VAT' => 'Total price (excluding VAT) of the order',
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
        'BLOCK_MOD' => 'Place order modifiers between these variables',
        'VAR_M_TYPE' => 'Order modifier type (discount / charge) (use within a table row)',
        'VAR_M_DESCRIPTION' => 'Order modifier description (use within a table row)',
        'VAR_M_QUANTITY' => 'Order modifier quantity (use within a table row)',
        'VAR_M_QUANTITY_TYPE' => 'Order modifier quantity type (fixed / percentage) (use within a table row)',
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
        'VAR_TRANS_FEE'            => 'Value of the transaction fee',
        'VAR_TRANS_FEE_LABEL'       => 'Label of the transaction fee',
        'VAR_USDC_WALLET_ADDRESS'   => 'USDC wallet address',
        'VAR_TOTAL_AFFECTED_BY_MODS' => 'Subtotal affected by price modifiers',
        'VAR_PO_NUMBER' => 'PO Item number (use within a table row)',
        'VAR_PO_RESOURCE' => 'PO Item resource (use within a table row)',
        'VAR_PO_DATE' => 'PO Item date (use within a table row)',
        'VAR_PO_DELIVERY_DATE' => 'PO Item delivery date (use within a table row)',
        'VAR_PO_PRICE' => 'PO Item price (use within a table row)',
        'VAR_PO_STATUS' => 'PO Item status (use within a table row)',
        'VAR_PO_CURRENCY' => 'PO Item customer currency (use within a table row)',
        'VAR_PO_TOTAL_PRICE' => 'PO Total price (including VAT) of the order',
        'VAR_PO_TOTAL_VAT' => 'PO Total VAT of the order',
        'VAR_PO_WITHOUT_VAT' => 'PO Total price (excluding VAT) of the order',
        'VAR_PO_TOTAL_WITHOUT_MODS' => 'PO Total price without discounts and markups',
        'VAR_PO_TOTAL_AFFECTED_BY_MODS' => 'PO Subtotal affected by price modifiers',
        'VAR_PO_TRANS_FEE'            => 'Value of the transaction fee',
        'VAR_PO_TRANS_FEE_LABEL'       => 'Label of the transaction fee',
        'VAR_E_NAME' => 'Employee Item number (use within a table row)',
        'VAR_E_TYPE' => 'Employee Item type (use within a table row)',
        'VAR_E_HOURS' => 'Employee Item hours (use within a table row)',
        'VAR_E_COST' => 'Employee Item costs (use within a table row)',
        'VAR_E_TOTAL_COSTS' => 'Employees total costs (use within a table row)',
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

    public function export(CanBeExported $order, $template_id, $type)
    {
        if ($media = $order->getMedia('document_order')->first()) {
            $docx = new CreateDocxFromTemplate($media->getPath());
            if ($type == 'docx') {
                return response()->download($media->getPath(), $media->file_name);
            }

            $docx->transformDocument($media->getPath(), $this->getPdfPath($media->getPath()));
            return Response::make(file_get_contents($this->getPdfPath($media->getPath())), 200, [
              'Content-Type' => 'application/pdf',
              'Content-Disposition' => 'inline; filename="order.pdf' . '"'
            ]);
        } else {
            //Get template
            $docx = new CreateDocxFromTemplate($order->getTemplatePath($template_id));
            $docx->processTemplate($this->getVariables());

            // Remove price modifiers subtoal table row from template when old calculation logic is set
            if (empty($order->project->price_modifiers_calculation_logic)) {
                $referenceNode = array(
                'customQuery' => '//w:tbl/w:tr[contains(.,"SUBTOTAL AFFECTED BY PRICE MODIFIERS")]',
                );
                $docx->removeWordContent($referenceNode);
            }

            if ($order->currency_code == CurrencyCode::USD()->getIndex()) {
                $docx->deleteTemplateBlock('EU');
                $docx->removeTemplateVariable('VAR_BANK_IBAN', 'block', 'footer');
                $docx->removeTemplateVariable('VAR_BANK_BIC', 'block', 'footer');
            } elseif ($order->currency_code == CurrencyCode::USDC()->getIndex()) {
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

            $docx->replaceVariableByText(OrderResource::make($order)->resolve(), ['target' => 'document']);
            $docx->replaceVariableByText(OrderResource::make($order)->resolve(), ['target' => 'header']);
            $docx->replaceVariableByText(OrderResource::make($order)->resolve(), ['target' => 'footer']);
            $docx->clearBlocks();

          //Add modifiers of quote to template
            $modifiers = getPriceModifiers($order);
            $orderedPriceModifier = orderPriceModifiers($order, $modifiers);
            $modifiers = ModifierResource::collection($orderedPriceModifier)->resolve();

            if (!empty($modifiers)) {
                $docx->replaceTableVariable($modifiers, ['firstMatch' => true]);
            } else {
                $docx->deleteTemplateBlock('MOD');
                $docx->removeTemplateVariable('VAR_M_QUANTITY', 'inline');
                $docx->removeTemplateVariable('VAR_M_DESCRIPTION', 'inline');
            }
            $docx->clearBlocks();

          //Add items of order to template
            $orderItems = $order->items()->with('priceModifiers')->orderBy('order')->get();
            $items = ItemResource::collection($orderItems)->resolve();

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

            $purchaseOrders = $order->project->purchaseOrders()->with('resource')->get();
            $purchaseOrders = PurchaseOrderReportResource::collection($purchaseOrders)->resolve();

            if (!empty($purchaseOrders)) {
                $docx->replaceTableVariable($purchaseOrders, ['parseLineBreaks' => true]);
            } else {
                $docx->removeTemplateVariable('VAR_PO_NUMBER', 'inline');
                $docx->removeTemplateVariable('VAR_PO_RESOURCE', 'inline');
                $docx->removeTemplateVariable('VAR_PO_DATE', 'inline');
                $docx->removeTemplateVariable('VAR_PO_DELIVERY_DATE', 'inline');
                $docx->removeTemplateVariable('VAR_PO_PRICE', 'inline');
                $docx->removeTemplateVariable('VAR_PO_STATUS', 'inline');
            }

          // Employees costs
            $projectEmployees = $order->project->employees()->get();
            $projectEmployees = ProjectEmployeeReportResource::collection($projectEmployees)->resolve();

            if (!empty($projectEmployees)) {
                $docx->replaceTableVariable($projectEmployees, ['parseLineBreaks' => true]);
            } else {
                $docx->removeTemplateVariable('VAR_E_NAME', 'inline');
                $docx->removeTemplateVariable('VAR_E_TYPE', 'inline');
                $docx->removeTemplateVariable('VAR_E_HOURS', 'inline');
                $docx->removeTemplateVariable('VAR_E_COST', 'inline');
            }

          //Create docx file
            $docx->createDocx($order->getExportPath());
            $media = $order->addMedia($order->getExportPath())->toMediaCollection($order->getExportMediaCollection());

          //Download docx file
            if ($type == 'docx') {
                return response()->download($media->getPath(), 'order.docx', ['Content-Type' => 'application/docx'], 'inline')->deleteFileAfterSend(true);
            }

            if ($type == 'xlsx') {
                return Excel::download(new OrderReportExport($order, $items, $purchaseOrders, $projectEmployees), $media->file_name.'.xlsx');
            }

          //Show pdf
            $docx->transformDocument($media->getPath(), $this->getPdfPath($media->getPath()));
            $response =  Response::make(file_get_contents($this->getPdfPath($media->getPath())), 200, [
              'Content-Type' => 'application/pdf',
              'Content-Disposition' => 'inline; filename="quote.pdf' . '"'
            ]);
            $media->delete();
            return $response;
        }
    }
}
