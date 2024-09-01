<?php

namespace App\Models;

use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class SalesCommission extends Model
{
    use Notifiable;
    use Uuids;
    use AutoElastic;

    protected $connection = 'mysql';

    const DEFAULT_COMMISSION = 3;
    const DEFAULT_LEAD_COMMISSION = 10;
    const DEFAULT_SECOND_SALE = 5;
    const DEFAULT_LEAD_COMMISSION_B = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['sales_person_id', 'commission', 'second_sale_commission', 'commission_model'];

    protected $mappingProperties = [
        'sales_person_id' => [
            'type' => 'keyword',
        ],
        'commission' => [
            'type' => 'float'
        ],
    ];

    public function salesperson()
    {
        return $this->belongsTo('App\Models\User', 'sales_person_id');
    }
}
