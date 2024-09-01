<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Jobs\ElasticUpdateAssignment;
use App\Models\User;

class UserObserver
{
    public function updated(User $user)
    {
        if ($user->company_id && UserRole::isSales($user->role) && ($user->isDirty('first_name') || $user->isDirty('last_name'))) {
            $id = $user->company_id;
            ElasticUpdateAssignment::dispatch($id, User::class, $user->id)->onQueue('low');
        }
    }
}
