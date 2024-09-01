<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class LoanPaymentLog extends Model
{
    use Uuids,
        OnTenant;

    protected $fillable = [
        'loan_id',
        'amount',
        'admin_amount',
        'pay_date',
    ];

    protected $casts = [
        'amount' => 'float',
        'admin_amount' => 'float',
    ];

    protected $dates = [
        'pay_date',
        'created_at',
        'updated_at',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(CompanyLoan::class, 'loan_id');
    }
}
