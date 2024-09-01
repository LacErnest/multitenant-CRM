<?php

namespace App\Listeners\EmailSentFactory;

use Tenancy\Hooks\Database\Events\Drivers\Creating;

interface EmailSentBuilder
{
    public function event($tenant, $data);
}
