<?php

namespace App\Http\Requests\EmailTemplate;

use App\Enums\NotificationReminderType;
use App\Http\Requests\BaseRequest;

class EmailTemplateUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'sender_id' => [
                'required',
                'exists:tenant.smtp_settings,id',
            ],
            'design_template_id'=>[
                'required',
                'exists:tenant.design_templates,id',
            ],
            'title' => [
                'required',
                'string',
                'max:255'
            ],
            'cc_addresses' => [
                'required',
                'array'
            ],
            'cc_addresses.*' => [
                'required',
                'email',
                'max:255'
            ],
            'reminder_ids' => [
                'array'
            ],
            'reminder_ids.*' => [
                'nullable',
                'exists:tenant.email_reminders,id',
            ],
            'reminder_types' => [
                'array'
            ],
            'reminder_types.*' => [
                'required',
                'enum:' . NotificationReminderType::class,
            ],
            'reminder_values' => [
                'array'
            ],
            'reminder_values.*' => [
                'required',
                'numeric',
            ],
            'reminder_templates' => [
                'array'
            ],
            'reminder_templates.*' => [
                'required',
                'exists:tenant.design_templates,id',
            ],
        ];
    }
}
