<?php

namespace App\Rules;

use App\Enums\EntityPenaltyType;
use Illuminate\Contracts\Validation\Rule;

class PenaltyRule implements Rule
{
    private $penaltyType;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($penaltyType)
    {
        $this->penaltyType = $penaltyType;
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
        if ($this->penaltyType == EntityPenaltyType::fixed()->getIndex()) {
            return $value > 0;
        } elseif ($this->penaltyType == EntityPenaltyType::percentage()->getIndex()) {
            return $value > 0 && $value < 100;
        }

        return in_array($value, [10, 20, 50]);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The selected Penalty is invalid.';
    }
}
