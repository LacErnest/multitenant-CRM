<?php

namespace App\Observers;

use App\Jobs\ElasticUpdateAssignment;
use App\Models\Contact;

class ContactObserver
{
    public function updated(Contact $contact)
    {
        if ($contact->isDirty('first_name') || $contact->isDirty('last_name')) {
            $id = getTenantWithConnection();
            ElasticUpdateAssignment::dispatch($id, Contact::class, $contact->id)->onQueue('low');
        }
    }
}
