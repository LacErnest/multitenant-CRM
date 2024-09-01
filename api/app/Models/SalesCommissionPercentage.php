<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class SalesCommissionPercentage extends Model
{
    use Notifiable;

    protected $connection = 'mysql';

    protected $fillable = ['commission_percentage', 'order_id', 'sales_person_id','type','invoice_id'];

    public function salesPerson()
    {
        return $this->belongsTo('App\Models\User', 'sales_person_id');
    }

    public function order()
    {
        return $this->belongsTo('App\Models\Order');
    }
}
