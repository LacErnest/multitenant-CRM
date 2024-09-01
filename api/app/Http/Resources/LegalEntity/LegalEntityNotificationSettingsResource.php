<?php

namespace App\Http\Resources\LegalEntity;

use App\Models\Contact;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalEntityNotificationSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $contacts = [];
        if (!empty($this->notification_contacts)) {
            $contacts = empty($this->notification_contacts) ? [] : Contact::whereIn('id', $this->notification_contacts)->get()
            ->map(function ($contact) {
                return $contact->first_name . ' ' . $contact->last_name;
            })->toArray();
        }

        return [
          'id'                                    => $this->id,
          'enable_submited_invoice_notification'  => $this->enable_submited_invoice_notification,
          'notification_footer'                   => $this->notification_footer,
          'notification_contacts'                 => $this->notification_contacts,
          'notification_contacts_names'            => $contacts,
          'legal_entity_id'                       => $this->legal_entity_id,
          'created_at'                            => $this->created_at,
          'updated_at'                            => $this->updated_at,
        ];
    }
}
