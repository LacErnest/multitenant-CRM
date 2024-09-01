<?php

namespace App\Models;

use App\Enums\EmployeeType;
use App\Http\Resources\Project\ProjectResource;
use App\Traits\Models\AutoElastic;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class Project extends Model
{
    use uuids;
    use OnTenant;
    use AutoElastic;

    protected $fillable = [
        'name',
        'contact_id',
        'project_manager_id',
        'sales_person_id',
        'budget',
        'budget_usd',
        'employee_costs',
        'employee_costs_usd',
        'external_employee_costs',
        'external_employee_costs_usd',
        'purchase_order_project',
        'second_sales_person_id',
        'price_modifiers_calculation_logic'
    ];

    protected $resourceClass = ProjectResource::class;

    protected $dates = ['created_at', 'updated_at'];

    protected $indexSettings = [
        'analysis' => [
            'normalizer' => [
                'to_lowercase' => [
                    'type' => 'custom',
                    'filter' => ['lowercase']
                ]
            ]
        ]
    ];

    protected $mappingProperties = [
        'id' => [
            'type' => 'keyword',
        ],
        'name' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'contact_id' => [
            'type' => 'keyword'
        ],
        'contact' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'customer_id' => [
            'type' => 'keyword'
        ],
        'customer' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'project_manager_id' => [
            'type' => 'keyword'
        ],
        'project_manager' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'budget' => [
            'type' => 'float',
        ],
        'budget_usd' => [
            'type' => 'float',
        ],
        'po_costs' => [
            'type' => 'float',
        ],
        'po_vat' => [
            'type' => 'float',
        ],
        'employee_costs' => [
            'type' => 'float',
        ],
        'po_costs_usd' => [
            'type' => 'float',
        ],
        'po_vat_usd' => [
            'type' => 'float',
        ],
        'employee_costs_usd' => [
            'type' => 'float',
        ],
        'quotes' => [
            'type' => 'integer',
        ],
        'high_costs' => [
            'type' => 'boolean'
        ],
        'created_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'updated_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'price_modifiers_calculation_logic' => [
            'type' => 'integer',
        ],
        'sales_person_id' => [
            'type' => 'keyword',
        ],
        'sales_person' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
    ];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'project_employees')
            ->withTimestamps()
            ->withPivot('hours', 'employee_cost', 'currency_rate_dollar', 'currency_rate_employee', 'month');
    }

    public function quotes()
    {
        return $this->hasMany('App\Models\Quote');
    }

    public function order()
    {
        return $this->hasOne('App\Models\Order');
    }

    public function invoices()
    {
        return $this->hasMany('App\Models\Invoice');
    }

    public function contact()
    {
        return $this->belongsTo('App\Models\Contact');
    }

    public function salesPersons(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\User', getTenantConnectionName().'.'.'project_sales_persons')->withTimestamps()->withPivot('id');
    }

    public function leadGens(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\User', getTenantConnectionName().'.'.'project_lead_gens')->withTimestamps()->withPivot('id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany('App\Models\PurchaseOrder');
    }

    public function projectManager()
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public static function getResource()
    {
        return (new static)->resourceClass;
    }

    public function resourceInvoices()
    {
        return $this->invoices()
            ->with('purchaseOrder')
            ->whereHas('purchaseOrder', function ($q) {
                $q->where('resource_id', '!=', null);
            });
    }
}
