<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Models\Uuids;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class Address extends Model
{
    use uuids;
    use OnTenant;

    protected $fillable = ['addressline_1','addressline_2','city','region','postal_code','country','address_id'];

    public function resources()
    {
        return $this->hasMany('App\Models\Resource');
    }

    public function employees()
    {
        return $this->hasMany('App\Models\Employee');
    }
}
