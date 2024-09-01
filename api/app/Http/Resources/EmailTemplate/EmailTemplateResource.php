<?php

namespace App\Http\Resources\EmailTemplate;

use App\Http\Resources\DesignTemplate\DesignTemplateResource;
use App\Models\DesignTemplate;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                                => $this->id,
            'title'                             => $this->title,
            'cc_addresses'                      => $this->cc_addresses,
            'sender_id'                         => $this->smtp_setting_id,
            'default'                           => $this->default,
            'reminder_ids'                      => $this->reminders->pluck('id'),
            'reminder_types'                    => $this->reminders->pluck('type'),
            'reminder_values'                   => $this->reminders->pluck('value'),
            'reminder_templates'                => $this->reminders->pluck('design_template_id'),
            'reminder_design_templates'                => $this->reminders->map(function ($reminder) {
                return DesignTemplateResource::make($reminder->designTemplate);
            }),
            'design_template_id'                => $this->design_template_id,
            'design_template'                   => $this->designTemplate ? DesignTemplateResource::make($this->designTemplate) : null,
            'created_at'                        => $this->created_at,
            'updated_at'                        => $this->updated_at,
            'deleted_at'                        => $this->deleted_at,
        ];
    }
}
