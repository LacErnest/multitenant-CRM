<?php

namespace App\DTO\Settings;

use Spatie\DataTransferObject\DataTransferObject;

class CompanySettingDTO extends DataTransferObject
{
    public $max_commission_percentage = 100;

    public $sales_person_commission_limit = 1;

    public $vat_default_value = 0;

    public $vat_max_value = 20;

    public $project_management_default_value = 10;

    public $project_management_max_value = 50;

    public $special_discount_default_value = 5;

    public $special_discount_max_value = 20;

    public $director_fee_default_value = 10;

    public $director_fee_max_value = 50;

    public $transaction_fee_default_value = 2;

    public $transaction_fee_max_value = 50;
}
