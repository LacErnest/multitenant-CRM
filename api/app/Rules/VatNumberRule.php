<?php

namespace App\Rules;

use App\Enums\Country;
use Illuminate\Contracts\Validation\Rule;

class VatNumberRule implements Rule
{
    protected string $vatNumber;
    protected int $country;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(string $vatNumber, int $country)
    {
        $this->vatNumber = $vatNumber;
        $this->country = $country;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $eu_countries = Country::ISEUROPEANCOUNTRY;
        if ($this->vatNumber && $this->country && in_array($this->country, $eu_countries)) {
            if (!preg_match('/^[A-Za-z]{2,4}(?=.{2,12}$)[-_\s0-9]*(?:[a-zA-Z][-_\s0-9]*){0,2}$/', $this->vatNumber)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Vat number is not in correct format.';
    }
}
