<?php

namespace App\Repositories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class SettingRepository
{
    protected Setting $setting;

    public function __construct(Setting $setting)
    {
        $this->setting = $setting;
    }

    public function get()
    {
        return response()->json($this->setting->first());
    }

    public function update($data)
    {
        if (!$setting = $this->setting->first()) {
            throw new ModelNotFoundException();
        }

        $error = true;
        $errors = [];

        if (((isset($data['quote_number']) && $data['quote_number']) || $data['quote_number'] == 0) && !$error = $this->checkUpdateFormat($data['quote_number_format'], $setting->quote_number_format, 'quote_number_format')) {
            unset($data['quote_number']);
        } elseif (is_array($error)) {
            $errors = array_merge($errors, $error);
        }

        if (((isset($data['order_number']) && $data['order_number']) || $data['order_number'] == 0) && !$error = $this->checkUpdateFormat($data['order_number_format'], $setting->order_number_format, 'order_number_format')) {
            unset($data['order_number']);
        } elseif (is_array($error)) {
            $errors = array_merge($errors, $error);
        }

        if (((isset($data['invoice_number']) && $data['invoice_number']) || $data['invoice_number'] == 0) && !$error = $this->checkUpdateFormat($data['invoice_number_format'], $setting->invoice_number_format, 'invoice_number_format')) {
            unset($data['invoice_number']);
        } elseif (is_array($error)) {
            $errors = array_merge($errors, $error);
        }

        if (((isset($data['purchase_order_number']) && $data['purchase_order_number']) || $data['purchase_order_number'] == 0) && !$error = $this->checkUpdateFormat($data['purchase_order_number_format'], $setting->purchase_order_number_format, 'purchase_order_number_format')) {
            unset($data['purchase_order_number']);
        } elseif (is_array($error)) {
            $errors = array_merge($errors, $error);
        }

        if (Arr::exists($data, 'resource_invoice_number')) {
            if (((isset($data['resource_invoice_number']) && $data['resource_invoice_number']) || $data['resource_invoice_number'] == 0) && !$error = $this->checkUpdateFormat($data['resource_invoice_number_format'], $setting->resource_invoice_number_format, 'resource_invoice_number_format')) {
                unset($data['resource_invoice_number']);
            } elseif (is_array($error)) {
                $errors = array_merge($errors, $error);
            }
        }

        if (0 < count($errors)) {
            throw ValidationException::withMessages($errors);
        }

        $setting->update($data);
        return response()->json($setting);
    }

    private function checkUpdateFormat(string $requestFormat, string $settingFormat, string $field)
    {
        preg_match_all('/X+/', $requestFormat, $matches, PREG_OFFSET_CAPTURE);

        if (0 < count($matches[0])) {
            $lastMatch = $matches[0][count($matches[0]) - 1];
            $requestFormat = substr_replace($requestFormat, 'X', $lastMatch[1], strlen($lastMatch[0]));
        } else {
            return [$field => 'Number format must contain at least one numeric identifier [X].'];
        }

        if (0 < $lastMatch[1]) {
            $beforeChar = $requestFormat[$lastMatch[1] - 1];

            if (is_numeric($beforeChar)) {
                return [$field => 'Number format cannot contain numbers right before numeric identifier [X].'];
            }
        }

        if ($lastMatch[1] < (strlen($requestFormat) - 1)) {
            $afterChar = $requestFormat[$lastMatch[1] + 1];

            if (is_numeric($afterChar)) {
                return [$field => 'Number format cannot contain numbers right after numeric identifier [X].'];
            }
        }

        preg_match_all('/X+/', $settingFormat, $matches, PREG_OFFSET_CAPTURE);
        $lastMatch = $matches[0][count($matches[0]) - 1];
        $settingFormat = substr_replace($settingFormat, 'X', $lastMatch[1], strlen($lastMatch[0]));

        return $requestFormat != $settingFormat;
    }
}
