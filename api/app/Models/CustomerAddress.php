<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Models\Uuids;

class CustomerAddress extends Model
{
    use uuids;

    protected $connection = 'mysql';

    protected $fillable = ['addressline_1','addressline_2','city','region','postal_code','country','address_id'];

    public function customers()
    {
        return $this->hasMany('App\Models\Customer');
    }
}
