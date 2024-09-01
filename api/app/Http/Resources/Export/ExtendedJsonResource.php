<?php

namespace App\Http\Resources\Export;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Formaters\ExtendedCurrencyFormatter;

class ExtendedJsonResource extends JsonResource
{
    /**
     * @var ExtendedCurrencyFormatter;
     */
    protected $currencyFormatter;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->currencyFormatter = app('extendedCurrencyFormatter');
    }
}
