<?php

namespace App\Http\Requests\Quote;

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\Project;
use App\Models\Quote;
use Illuminate\Validation\Validator;

class CreateOrderFromQuoteRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isPm(auth()->user()->role) || UserRole::isHr(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role)) {
            return false;
        }
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'deadline' => 'date|after:'. now() . '|nullable'
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $project = Project::with('order')->findOrFail($this->route('project_id'));
            if ($project->order()->where('status', '>=', OrderStatus::delivered()->getIndex())->exists()) {
                $validator->errors()->add('order', 'This project has been delivered. No new orders allowed.');
            }
            if ($project->order()->doesntExist()) {
                if (empty($this->deadline)) {
                    $validator->errors()->add('deadline', 'A deadline for the order is required.');
                }
            }
        });
    }
}
