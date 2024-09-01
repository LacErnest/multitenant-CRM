<?php

namespace App\Http\Requests\Quote;

use App\Enums\CurrencyCode;
use App\Enums\DownPaymentAmountType;
use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Requests\BaseRequest;
use App\Models\Quote;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Support\Arr;

class QuoteUpdateRequest extends BaseRequest
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
          'name' => [
              'required',
              'max:191'
          ],
          'contact_id' => [
              'uuid',
              'exists:contacts,id',
          ],
          'sales_person_id.*' => [
              'required',
              'uuid',
              Rule::exists('users', 'id')
                  ->where(function ($query) {
                      $query->where('company_id', getTenantWithConnection())->orWhereNull('company_id');
                  })
                  ->whereIn('role', [UserRole::sales()->getIndex(), UserRole::owner()->getIndex(), UserRole::admin()->getIndex()]),
          ],
          'date' => [
              'date',
          ],
          'expiry_date' => [
              'date',
              'after_or_equal:date',
          ],
          'reference' => [
              'string',
              'nullable',
              'max:50',
          ],
          'currency_code' => [
              'integer',
              'enum:' . CurrencyCode::class,
          ],
          'manual_input' => [
              'boolean',
              'required',
          ],
          'down_payment' => [
              'nullable',
              'integer',
          ],
          'down_payment_type' => 'nullable|integer|enum:'. DownPaymentAmountType::class,
          'master' => [
              'boolean'
          ],
          'vat_status' => [
              'nullable',
              'integer',
              'enum:' . VatStatus::class,
          ],
          'vat_percentage' => [
              'numeric',
              'nullable',
              'min:0',
              'max:100',
          ],
          'second_sales_person_id.*' => [
              'nullable',
              'uuid',
              Rule::exists('users', 'id')
                  ->where(function ($query) {
                      $query->where('company_id', getTenantWithConnection())->orWhereNull('company_id');
                  })
                  ->whereIn('role', [UserRole::sales()->getIndex(), UserRole::owner()->getIndex(), UserRole::admin()->getIndex()]),
          ],
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
            $quote = Quote::with('project', 'project.contact')->findOrFail($this->route('quote_id'));

            if ($quote->shadow) {
                $validator->errors()->add('status', 'This quote is a shadow. No updates allowed.');
                return;
            }

            if (!QuoteStatus::isDraft($quote->status)) {
                $validator->errors()->add('status', 'Only quotes with status Draft can be updated.');
                return;
            }
            if ($quote->project->order()->where('status', '>=', OrderStatus::delivered()->getIndex())->exists()) {
                $validator->errors()->add('order', 'This project has been delivered. Updating quotes not allowed.');
            }
            if (!empty($this->contact_id)) {
                if ($quote->project->order()->exists() && !in_array($this->contact_id, $quote->project->contact->customer->contacts()->pluck('id')->toArray())) {
                    $validator->errors()->add('contact_id', 'An order has been created for this project. Wrong customer contact.');
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
