<?php

namespace App\Http\Requests\DesignTemplate;

use App\Enums\NotificationReminderType;
use App\Http\Requests\BaseRequest;

class DesignTemplateCreateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255'
            ],
            'subject' => [
                'required',
                'string',
                'max:255'
            ],
            'html' => [
                'required',
                'string',
            ],
            'design' => [
                'required',
                'string',
            ],
        ];
    }
}
