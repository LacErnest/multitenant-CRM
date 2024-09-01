<?php

namespace App\Http\Requests\Contact;


use App\Enums\ContactGenderTypes;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;


class ContactCreateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isOwner = UserRole::isOwner(auth()->user()->role);
        $isAccountant = UserRole::isAccountant(auth()->user()->role);
        $isAdmin = UserRole::isAdmin(auth()->user()->role);
        $isSales = UserRole::isSales(auth()->user()->role);

        return $isOwner || $isAccountant || $isAdmin || $isSales;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'first_name' => [
              'string',
              'max:128',
              'nullable',
          ],
          'last_name' => [
              'string',
              'max:128',
              'nullable',
          ],
          'email' => [
              'email',
              'max:128',
              'nullable',
          ],
          'department' => [
              'string',
              'max:128',
              'nullable',
          ],
          'title' => [
              'string',
              'max:128',
              'nullable',
          ],
          'phone_number' => [
              'string',
              'max:128',
              'unique:contacts,phone_number',
              'nullable',
          ],
          'primary_contact' => [
              'bool',
              'nullable',
          ],
          'gender' => [
              'required',
              'string',
              Rule::in(ContactGenderTypes::getValues()),
          ],
          'linked_in_profile' => [
              'string',
              'nullable',
              'regex:/http(s)?:\/\/([\w]+\.)?linkedin\.com\/in\/[A-z0-9_-]+\/?/'
          ],
        ];
    }


    /**
     * Configure the validator instance.
     *
     * @param  Validator $validator
     *
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!count($this->toArray())) {
                $validator->errors()->add('unavailable', 'Request must be have at least one param');
            }
        });
    }
}
