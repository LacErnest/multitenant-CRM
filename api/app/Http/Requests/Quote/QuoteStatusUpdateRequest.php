<?php

namespace App\Http\Requests\Quote;

use App\Enums\CurrencyCode;
use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\Project;
use App\Models\Quote;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Enums\DownPaymentAmountType;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class QuoteStatusUpdateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isPm(auth()->user()->role) || UserRole::isHr(auth()->user()->role) ||
          UserRole::isOwner_read(auth()->user()->role) || UserRole::isPm_restricted(auth()->user()->role)) {
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
          'status' => 'required|integer|enum:'.QuoteStatus::class,
          'reason_of_refusal' => 'string|nullable|max:250|required_if:status,'.QuoteStatus::declined()->getIndex(),
          'deadline' => 'date|nullable|required_if:status,'.QuoteStatus::ordered()->getIndex()
        ];
    }

    public function prepareForValidation()
    {
        if (!is_int($this->status)) {
            throw new BadRequestHttpException('Status field  not valid');
        }
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
            if ($this->status == QuoteStatus::draft()->getIndex() && auth()->user()->super_user) {
                return;
            }

            $quote = Quote::with('project')->findOrFail($this->route('quote_id'));

            if ($this->status != QuoteStatus::cancelled()->getIndex()) {
                if ($quote->project->order()->where('status', '>=', OrderStatus::delivered()->getIndex())->exists()) {
                    $validator->errors()->add('status', 'This project has been delivered. No new quotes allowed.');
                }
                if (!QuoteStatus::isDraft($quote->status) && !QuoteStatus::isSent($quote->status)) {
                    $validator->errors()->add('status', 'Only status Draft or Sent can be updated.');
                }
                if (QuoteStatus::isDraft($quote->status) && !QuoteStatus::isSent($this->status)) {
                    $validator->errors()->add('status', 'The status Draft can only be updated to Sent.');
                }
                if (QuoteStatus::isSent($quote->status) && (!QuoteStatus::isOrdered($this->status) && !QuoteStatus::isDeclined($this->status))) {
                    $validator->errors()->add('status', 'The status Sent can only be updated to Ordered or Declined.');
                }
                if (QuoteStatus::isSent($this->status) && $quote->total_price == 0) {
                    $validator->errors()->add('status', 'This quote has no value. Add items first before sending it.');
                }
            }
            if (!empty($this->status)) {
                if ($this->status != QuoteStatus::cancelled()->getIndex() && $quote->status === QuoteStatus::cancelled()->getIndex()) {
                    $validator->errors()->add('status', 'The status Cancelled can not be updated to another status.');
                }

                if ($this->status == QuoteStatus::cancelled()->getIndex()) {
                    if (!(UserRole::isOwner(auth()->user()->role) || UserRole::isAdmin(auth()->user()->role))) {
                        $validator->errors()->add('status', 'Only user with owner or admin role can set cancelled status');
                    }
                }
            }
        });
    }
}
