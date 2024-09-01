<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class ResourceLogin extends Model
{
    use uuids;
    use OnTenant;

    protected $fillable = ['resource_id', 'token'];
}
