<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class CompanyLoan extends Model
{
    use Uuids,
        OnTenant,
        SoftDeletes;

    protected $fillable = [
        'issued_at',
        'amount',
        'paid_at',
        'author_id',
        'admin_amount',
        'description',
        'amount_left',
        'admin_amount_left',
    ];

    protected $casts = [
        'amount' => 'float',
        'admin_amount' => 'float',
        'amount_left' => 'float',
        'admin_amount_left' => 'float',
    ];

    protected $dates = [
        'issued_at',
        'paid_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function paymentLogs(): HasMany
    {
        return $this->hasMany(LoanPaymentLog::class, 'loan_id');
    }
}
