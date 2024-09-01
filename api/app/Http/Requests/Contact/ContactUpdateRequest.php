<?php

namespace App\Http\Requests\Contact;

class ContactUpdateRequest extends ContactCreateRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
          'phone_number' => [
              'string',
              'max:128',
              'unique:contacts,phone_number,'.$this->contact->id,
              'nullable',
          ],
        ]);
    }
}
