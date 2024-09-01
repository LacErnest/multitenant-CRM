<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

class ContactImportUpdateRequest extends FormRequest
{
    protected string $existingContactId;

    public function __construct(string $existingContactId)
    {
        $this->existingContactId = $existingContactId;
    }
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
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
          'first_name' => [
              'string',
              'max:128',
              'required',
          ],
          'last_name' => [
              'string',
              'max:128',
              'required',
          ],
          'email' => [
              'email',
              'max:128',
              'unique:contacts,email,' . $this->existingContactId,
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
              'unique:contacts,phone_number,' . $this->existingContactId,
              'nullable',
          ],
          'linked_in_profile' => [
              'string',
              'nullable',
              'regex:/http(s)?:\/\/([\w]+\.)?linkedin\.com\/in\/[A-z0-9_-]+\/?/'
          ],
        ];
    }
}
