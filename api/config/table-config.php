<?php

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QuoteStatus;

return [
    'users' => [
        'all' => [
            [
                'prop' => 'first_name',
                'name' => 'first name',
                'type' => 'string'
            ],
            [
                'prop' => 'last_name',
                'name' => 'last name',
                'type' => 'string'
            ],
            [
                'prop' => 'email',
                'name' => 'email',
                'type' => 'string'
            ],
            [
                'prop' => 'role',
                'name' => 'role',
                'type' => 'enum',
                'enum' => 'userrole'
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date'
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date'
            ],
            [
                'prop' => 'disabled_at',
                'name' => 'disabled',
                'type' => 'boolean',
                'check_on' => false,
            ],
        ],
        'default' => [
            'first_name', 'last_name', 'email', 'role'
        ],
        'default_sorting' => [
            [
                'prop' => 'updated_at',
                'dir' => 'desc'
            ]
        ],
    ],
    'customers' => [
        'all' => [
            [
                'prop' => 'name',
                'name' => 'name',
                'type' => 'string'
            ],
            [
                'prop' => 'company',
                'name' => 'company',
                'type' => 'string',
                'model' => 'company'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'customerstatus'
            ],
            [
                'prop' => 'description',
                'name' => 'description',
                'type' => 'string',
            ],
            [
                'prop' => 'industry',
                'name' => 'industry',
                'type' => 'enum',
                'enum' => 'industrytype'

            ],
            [
                'prop' => 'email',
                'name' => 'email',
                'type' => 'string'
            ],
            [
                'prop' => 'tax_number',
                'name' => 'tax number',
                'type' => 'string'
            ],
            [
                'prop' => 'default_currency',
                'name' => 'currency',
                'type' => 'enum',
                'enum' => 'currencycode'
            ],
            [
                'prop' => 'website',
                'name' => 'website',
                'type' => 'string'
            ],
            [
                'prop' => 'phone_number',
                'name' => 'phone number',
                'type' => 'string'
            ],
            [
                'prop' => 'sales_person_id',
                'name' => 'sales person',
                'type' => 'uuid',
                'model' => 'user'
            ],
            [
                'prop' => 'primary_contact_id',
                'name' => 'primary contact',
                'type' => 'uuid',
                'model' => 'contact'
            ],
            [
                'prop' => 'average_collection_period',
                'name' => 'average collection period',
                'type' => 'integer'
            ],
            [
                'prop' => 'billing_addressline_1',
                'name' => 'billing address line 1',
                'type' => 'string',
            ],
            [
                'prop' => 'billing_addressline_2',
                'name' => 'billing address line 2',
                'type' => 'string',
            ],
            [
                'prop' => 'billing_city',
                'name' => 'billing city',
                'type' => 'string',
            ],
            [
                'prop' => 'billing_region',
                'name' => 'billing region',
                'type' => 'string',
            ],
            [
                'prop' => 'billing_postal_code',
                'name' => 'billing postal code',
                'type' => 'string',
            ],
            [
                'prop' => 'billing_country',
                'name' => 'billing country',
                'type' => 'enum',
                'enum' => 'country'
            ],
            [
                'prop' => 'operational_addressline_1',
                'name' => 'operational address line 1',
                'type' => 'string',
            ],
            [
                'prop' => 'operational_addressline_2',
                'name' => 'operational address line 2',
                'type' => 'string',
            ],
            [
                'prop' => 'operational_city',
                'name' => 'operational city',
                'type' => 'string',
            ],
            [
                'prop' => 'operational_region',
                'name' => 'operational region',
                'type' => 'string',
            ],
            [
                'prop' => 'operational_postal_code',
                'name' => 'operational postal code',
                'type' => 'string',
            ],
            [
                'prop' => 'operational_country',
                'name' => 'operational country',
                'type' => 'enum',
                'enum' => 'country'
            ],
            [
                'prop' => 'non_vat_liable',
                'name' => 'non vat liable',
                'type' => 'boolean',
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date'
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date'
            ],
            [
                'prop' => 'intra_company',
                'name' => 'intra company',
                'type' => 'boolean',
            ]
        ],
        'default' => [
            'name', 'company', 'status', 'email', 'phone_number', 'industry'
        ]
    ],
    'contacts' => [
        'all' => [
            [
                'prop' => 'first_name',
                'name' => 'first name',
                'type' => 'string'
            ],
            [
                'prop' => 'last_name',
                'name' => 'last name',
                'type' => 'string'
            ],
            [
                'prop' => 'email',
                'name' => 'email',
                'type' => 'string'
            ],
            [
                'prop' => 'phone_number',
                'name' => 'phone number',
                'type' => 'string'
            ],
            [
                'prop' => 'customer_id',
                'name' => 'customer',
                'type' => 'uuid',
                'model' => 'customer'
            ],
            [
                'prop' => 'department',
                'name' => 'department',
                'type' => 'string'
            ],
            [
                'prop' => 'title',
                'name' => 'title',
                'type' => 'string'
            ],
            [
                'prop' => 'gender',
                'name' => 'gender',
                'type' => 'string'
            ],
            [
                'prop' => 'linked_in_profile',
                'name' => 'linkedIn profile',
                'type' => 'string'
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date'
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date'
            ]
        ],
        'default' => [
            'first_name', 'last_name', 'email', 'phone_number', 'customer_id', 'title', 'department'
        ]
    ],
    'projects' => [
        'all' => [
            [
                'prop' => 'name',
                'name' => 'name',
                'type' => 'string'
            ],
            [
                'prop' => 'contact_id',
                'name' => 'contact',
                'type' => 'uuid',
                'model' => 'contact'
            ],
            [
                'prop' => 'project_manager_id',
                'name' => 'project manager',
                'type' => 'uuid',
                'model' => 'resource'
            ],
            [
                'prop' => 'sales_person_id',
                'name' => 'sales person',
                'type' => 'uuid',
                'model' => 'user'
            ],
            [
                'prop' => 'customer_id',
                'name' => 'customer',
                'type' => 'uuid',
                'model' => 'customer'
            ],
            [
                'prop' => 'budget',
                'name' => 'budget',
                'type' => 'decimal'
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date'
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date'
            ]
        ],
        'default' => [
            'name', 'contact_id', 'project_manager_id', 'sales_person_id', 'customer_id', 'budget'
        ],
        'custom_filters' => [
            'no_quotes' => [
                [
                    'prop' => 'quotes',
                    'type' => 'enum',
                    'value' => [
                        0
                    ]
                ]
            ],
            'no_costs' => [
                [
                    'prop' => 'po_costs',
                    'type' => 'enum',
                    'value' => [
                        0
                    ]
                ],
                [
                    'prop' => 'employee_costs',
                    'type' => 'enum',
                    'value' => [
                        0
                    ]
                ]
            ],
            'high_costs' => [
                [
                    'prop' => 'high_costs',
                    'type' => 'enum',
                    'value' => [
                        true
                    ]
                ]
            ]
        ]
    ],
    'resources' => [
        'all' => [
            [
                'prop' => 'type',
                'name' => 'type',
                'type' => 'enum',
                'enum' => 'resourcetype'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'resourcestatus'
            ],
            [
                'prop' => 'name',
                'name' => 'name',
                'type' => 'string'
            ],
            [
                'prop' => 'first_name',
                'name' => 'first name',
                'type' => 'string'
            ],
            [
                'prop' => 'last_name',
                'name' => 'last name',
                'type' => 'string'
            ],
            [
                'prop' => 'email',
                'name' => 'email',
                'type' => 'string'
            ],
            [
                'prop' => 'tax_number',
                'name' => 'tax number',
                'type' => 'string'
            ],
            [
                'prop' => 'default_currency',
                'name' => 'currency',
                'type' => 'enum',
                'enum' => 'currencycode'
            ],
            [
                'prop' => 'hourly_rate',
                'name' => 'hourly rate',
                'type' => 'decimal'
            ],
            [
                'prop' => 'daily_rate',
                'name' => 'daily rate',
                'type' => 'decimal'
            ],
            [
                'prop' => 'phone_number',
                'name' => 'phone number',
                'type' => 'string'
            ],
            [
                'prop' => 'legal_entity_id',
                'name' => 'legal entity',
                'type' => 'uuid',
                'model' => 'legal_entity'
            ],
            [
                'prop' => 'addressline_1',
                'name' => 'address line 1',
                'type' => 'string',
            ],
            [
                'prop' => 'addressline_2',
                'name' => 'address line 2',
                'type' => 'string',
            ],
            [
                'prop' => 'city',
                'name' => 'city',
                'type' => 'string',
            ],
            [
                'prop' => 'region',
                'name' => 'region',
                'type' => 'string',
            ],
            [
                'prop' => 'postal_code',
                'name' => 'postal code',
                'type' => 'string',
            ],
            [
                'prop' => 'country',
                'name' => 'country',
                'type' => 'enum',
                'enum' => 'country'
            ],
            [
                'prop' => 'job_title',
                'name' => 'job title',
                'type' => 'string'
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date'
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date'
            ]
        ],
        'default' => [
            'name', 'type', 'status', 'email', 'phone_number'
        ]
    ],
    'employees' => [
        'all' => [
            [
                'prop' => 'type',
                'name' => 'type',
                'type' => 'enum',
                'enum' => 'employeetype',
            ],
            [
                'prop' => 'is_pm',
                'name' => 'project manager',
                'type' => 'boolean',
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'employeestatus',
            ],
            [
                'prop' => 'first_name',
                'name' => 'first name',
                'type' => 'string',
            ],
            [
                'prop' => 'last_name',
                'name' => 'last name',
                'type' => 'string',
            ],
            [
                'prop' => 'email',
                'name' => 'email',
                'type' => 'string',
            ],
            [
                'prop' => 'role',
                'name' => 'role',
                'type' => 'string',
            ],
            [
                'prop' => 'salary',
                'name' => 'salary',
                'type' => 'decimal'
            ],
            [
                'prop' => 'working_hours',
                'name' => 'working hours',
                'type' => 'integer',
            ],
            [
                'prop' => 'default_currency',
                'name' => 'currency',
                'type' => 'enum',
                'enum' => 'currencycode',
            ],
            [
                'prop' => 'started_at',
                'name' => 'started at',
                'type' => 'date',
            ],
            [
                'prop' => 'linked_in_profile',
                'name' => 'linkedIn profile',
                'type' => 'string',
            ],
            [
                'prop' => 'facebook_profile',
                'name' => 'facebook profile',
                'type' => 'string',
            ],
            [
                'prop' => 'phone_number',
                'name' => 'phone number',
                'type' => 'string',
            ],
            [
                'prop' => 'legal_entity_id',
                'name' => 'legal entity',
                'type' => 'uuid',
                'model' => 'legal_entity'
            ],
            [
                'prop' => 'addressline_1',
                'name' => 'address line 1',
                'type' => 'string',
            ],
            [
                'prop' => 'addressline_2',
                'name' => 'address line 2',
                'type' => 'string',
            ],
            [
                'prop' => 'city',
                'name' => 'city',
                'type' => 'string',
            ],
            [
                'prop' => 'region',
                'name' => 'region',
                'type' => 'string',
            ],
            [
                'prop' => 'postal_code',
                'name' => 'postal code',
                'type' => 'string',
            ],
            [
                'prop' => 'country',
                'name' => 'country',
                'type' => 'enum',
                'enum' => 'country',
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date',
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date',
            ],
        ],
        'default' => [
            'first_name',
            'last_name',
            'type',
            'status',
            'email',
            'phone_number'
        ],
    ],
    'project_employees' => [
        'all' => [
            [
                'prop' => 'employee_id',
                'name' => 'employee name',
                'type' => 'uuid',
                'model' => 'employee'
            ],
            [
                'prop' => 'employee',
                'name' => 'employee',
                'type' => 'string'
            ],
            [
                'prop' => 'first_name',
                'name' => 'first name',
                'type' => 'string'
            ],
            [
                'prop' => 'last_name',
                'name' => 'last name',
                'type' => 'string'
            ],
            [
                'prop' => 'type',
                'name' => 'type',
                'type' => 'enum',
                'enum' => 'employeetype'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'employeestatus'
            ],
            [
                'prop' => 'email',
                'name' => 'email',
                'type' => 'string'
            ],
            [
                'prop' => 'phone_number',
                'name' => 'phone number',
                'type' => 'string'
            ],
            [
                'prop' => 'hours',
                'name' => 'planned hours',
                'type' => 'integer'
            ],
            [
                'prop' => 'employee_cost',
                'name' => 'employee cost',
                'type' => 'decimal'
            ],
            [
                'prop' => 'is_borrowed',
                'name' => 'borrowed',
                'type' => 'boolean'
            ],
            [
                'prop' => 'details',
                'name' => 'details',
                'type' => 'object'
            ]
        ],
        'default' => ['employee', 'type', 'status', 'email', 'phone_number', 'hours', 'employee_cost', 'is_borrowed', 'details']
    ],
    'quotes' => [
        'all' => [
            [
                'prop' => 'intra_company',
                'name' => 'Intra Company',
                'type' => 'enum',
                'enum' => 'projecttype',
                'filterable' => 'invisible',
                'cast' => 'boolean',
            ],
            [
                'prop' => 'project',
                'name' => 'project',
                'type' => 'string'
            ],
            [
                'prop' => 'number',
                'name' => 'number',
                'type' => 'string'
            ],
            [
                'prop' => 'customer_id',
                'name' => 'customer',
                'type' => 'uuid',
                'model' => 'customer'
            ],
            [
                'prop' => 'contact_id',
                'name' => 'contact',
                'type' => 'uuid',
                'model' => 'contact'
            ],
            [
                'prop' => 'order_id',
                'name' => 'order',
                'type' => 'uuid',
                'model' => 'order',
                'cast' => 'order'
            ],
            [
                'prop' => 'sales_person_id',
                'name' => 'sales person',
                'type' => 'uuid',
                'model' => 'user'
            ],
            [
                'prop' => 'second_sales_person_id',
                'name' => 'lead generation',
                'type' => 'uuid',
                'model' => 'user'
            ],
            [
                'prop' => 'date',
                'name' => 'date',
                'type' => 'date'
            ],
            [
                'prop' => 'expiry_date',
                'name' => 'expiry date',
                'type' => 'date'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'quotestatus'
            ],
            [
                'prop' => 'reason_of_refusal',
                'name' => 'reason of refusal',
                'type' => 'string'
            ],
            [
                'prop' => 'reference',
                'name' => 'reference',
                'type' => 'string'
            ],
            [
                'prop' => 'currency_code',
                'name' => 'currency code',
                'type' => 'enum',
                'enum' => 'currencycode',
                /*'parent' => 'total_price'*/
            ],
            [
                'prop' => 'customer_currency_code',
                'name' => 'customer currency code',
                'type' => 'enum',
                'enum' => 'currencycode'
            ],
            [
                'prop' => 'total_price',
                'name' => 'total price (EUR)',
                'format' => 'in_euro',
                'filter_name' => 'total price',
                'type' => 'decimal',
                'children' => ['total_price_usd', 'customer_total_price']
            ],
            [
                'prop' => 'total_price_usd',
                'name' => 'total price (USD)',
                'format' => 'in_dollar',
                'type' => 'decimal',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'customer_total_price',
                'name' => 'Total Price (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'currency_rate_customer',
                'name' => 'conversion rate',
                'type' => 'decimal',
                'format' => 'number'
            ],
            [
                'prop' => 'legal_entity_id',
                'name' => 'legal entity',
                'type' => 'uuid',
                'model' => 'legal_entity'
            ],
            [
                'prop' => 'intra_company',
                'name' => 'intra_company',
                'type' => 'boolean',
                'hidden' => true
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date'
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date'
            ],
        ],
        'default' => [
            'project', 'number', 'order_id', 'customer_id', 'contact_id', 'sales_person_id', 'second_sales_person_id', 'date', 'expiry_date', 'status', 'total_price', 'total_price_usd', 'customer_total_price'
        ],
        'default_filter' => [
            [
                'prop' => 'status',
                'type' => 'enum',
                'value' => [
                    QuoteStatus::draft()->getIndex(),
                    QuoteStatus::sent()->getIndex(),
                    QuoteStatus::declined()->getIndex(),
                    QuoteStatus::ordered()->getIndex(),
                    QuoteStatus::invoiced()->getIndex(),
                ]
            ]
        ],
        'order_quotes' => [
            [
                'prop' => 'number',
                'name' => 'number',
                'type' => 'string'
            ],
            [
                'prop' => 'date',
                'name' => 'date',
                'type' => 'date'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'quotestatus'
            ]
        ]
    ],
    'project_quotes' => [
        'all' => [
            [
                'prop' => 'project',
                'name' => 'project',
                'type' => 'string'
            ],
            [
                'prop' => 'number',
                'name' => 'number',
                'type' => 'string'
            ],
            [
                'prop' => 'date',
                'name' => 'date',
                'type' => 'date'
            ],
            [
                'prop' => 'expiry_date',
                'name' => 'expiry date',
                'type' => 'date'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'quotestatus'
            ],
            [
                'prop' => 'total_price',
                'name' => 'total price (EUR)',
                'format' => 'in_euro',
                'filter_name' => 'total price',
                'type' => 'decimal',
                'children' => ['total_price_usd', 'customer_total_price']
            ],
            [
                'prop' => 'total_price_usd',
                'name' => 'total price (USD)',
                'format' => 'in_dollar',
                'type' => 'decimal',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'customer_total_price',
                'name' => 'Total Price (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'currency_rate_customer',
                'name' => 'conversion rate',
                'type' => 'decimal',
                'format' => 'number'
            ],
            [
                'prop' => 'customer_id',
                'name' => 'customer',
                'type' => 'uuid',
                'model' => 'customer',
            ],
        ],
        'default' => ['project', 'number', 'customer_id', 'date', 'expiry_date', 'status', 'total_price', 'total_price_usd', 'customer_total_price']
    ],
    'orders' => [
        'all' => [
            [
                'prop' => 'intra_company',
                'name' => 'Intra Company',
                'type' => 'enum',
                'enum' => 'projecttype',
                'filterable' => 'invisible',
                'cast' => 'boolean',
            ],
            [
                'prop' => 'project',
                'name' => 'project',
                'type' => 'string'
            ],
            [
                'prop' => 'number',
                'name' => 'number',
                'type' => 'string'
            ],
            [
                'prop' => 'project_manager_id',
                'name' => 'project manager',
                'type' => 'uuid',
                'model' => 'employee',
            ],
            [
                'prop' => 'quote_id',
                'name' => 'quote',
                'type' => 'uuid',
                'model' => 'quote',
                'cast' => 'quote'
            ],
            [
                'prop' => 'customer_id',
                'name' => 'customer',
                'type' => 'uuid',
                'model' => 'customer'
            ],
            [
                'prop' => 'contact_id',
                'name' => 'contact',
                'type' => 'uuid',
                'model' => 'contact'
            ],
            [
                'prop' => 'date',
                'name' => 'date',
                'type' => 'date'
            ],
            [
                'prop' => 'deadline',
                'name' => 'deadline',
                'type' => 'date'
            ],
            [
                'prop' => 'delivered_at',
                'name' => 'delivered at',
                'type' => 'date'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'orderstatus'
            ],
            [
                'prop' => 'reference',
                'name' => 'reference',
                'type' => 'string'
            ],
            [
                'prop' => 'currency_code',
                'name' => 'currency code',
                'type' => 'enum',
                'enum' => 'currencycode'
            ],
            [
                'prop' => 'total_price',
                'name' => 'total price (EUR)',
                'filter_name' => 'total price',
                'type' => 'decimal',
                'format' => 'in_euro',
                'children' => ['total_price_usd', 'customer_total_price']
            ],
            [
                'prop' => 'total_price_usd',
                'name' => 'total price (USD)',
                'type' => 'decimal',
                'format' => 'in_dollar',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'customer_total_price',
                'name' => 'Total Price (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'markup',
                'name' => 'GM %',
                'type' => 'percentage'
            ],
            [
                'prop' => 'gross_margin',
                'name' => 'GM (EUR)',
                'filter_name' => 'GM',
                'type' => 'decimal',
                'format' => 'in_euro',
                'children' => ['gross_margin_usd', 'customer_gross_margin']
            ],
            [
                'prop' => 'gross_margin_usd',
                'name' => 'GM (USD)',
                'type' => 'decimal',
                'format' => 'in_dollar',
                'parent' => 'gross_margin'
            ],
            [
                'prop' => 'customer_gross_margin',
                'name' => 'GM (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'gross_margin'
            ],
            [
                'prop' => 'costs',
                'name' => 'costs (EUR)',
                'filter_name' => 'costs',
                'type' => 'decimal',
                'format' => 'in_euro',
                'children' => ['costs_usd', 'customer_costs']
            ],
            [
                'prop' => 'costs_usd',
                'name' => 'costs (USD)',
                'type' => 'decimal',
                'format' => 'in_dollar',
                'parent' => 'costs'
            ],
            [
                'prop' => 'customer_costs',
                'name' => 'Costs (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'costs'
            ],
            [
                'prop' => 'potential_gm',
                'name' => 'potential GM (EUR)',
                'filter_name' => 'potential GM',
                'type' => 'decimal',
                'format' => 'in_euro',
                'children' => ['potential_gm_usd', 'customer_potential_gm']
            ],
            [
                'prop' => 'potential_gm_usd',
                'name' => 'potential GM (USD)',
                'type' => 'decimal',
                'format' => 'in_dollar',
                'parent' => 'potential_gm',
            ],
            [
                'prop' => 'customer_potential_gm',
                'name' => 'Potential GM (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'potential_gm',
            ],
            [
                'prop' => 'potential_costs',
                'name' => 'potential costs (EUR)',
                'filter_name' => 'potential costs',
                'type' => 'decimal',
                'format' => 'in_euro',
                'children' => ['potential_costs_usd', 'customer_potential_costs']
            ],
            [
                'prop' => 'potential_costs_usd',
                'name' => 'potential costs (USD)',
                'type' => 'decimal',
                'format' => 'in_dollar',
                'parent' => 'potential_costs',
            ],
            [
                'prop' => 'customer_potential_costs',
                'name' => 'potential costs (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'potential_costs',
            ],
            [
                'prop' => 'potential_markup',
                'name' => 'potential GM %',
                'type' => 'percentage'
            ],
            [
                'prop' => 'currency_rate_customer',
                'name' => 'conversion rate',
                'type' => 'decimal',
                'format' => 'number'
            ],
            [
                'prop' => 'legal_entity_id',
                'name' => 'legal entity',
                'type' => 'uuid',
                'model' => 'legal_entity'
            ],
            [
                'prop' => 'intra_company',
                'name' => 'intra_company',
                'type' => 'boolean',
                'hidden' => true
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date'
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date'
            ]
        ],
        'default' => [
            'project', 'number', 'project_manager_id', 'quote_id', 'customer_id', 'contact_id', 'date', 'status', 'total_price', 'markup', 'total_price_usd', 'customer_total_price'
        ],
        'default_filter' => [
            [
                'prop' => 'status',
                'type' => 'enum',
                'value' => [
                    OrderStatus::draft()->getIndex(),
                    OrderStatus::active()->getIndex(),
                    OrderStatus::delivered()->getIndex(),
                    OrderStatus::invoiced()->getIndex()

                ]
            ]
        ]
    ],
    'project_orders' => [
        'all' => [
            [
                'prop' => 'project',
                'name' => 'project',
                'type' => 'string'
            ],
            [
                'prop' => 'number',
                'name' => 'number',
                'type' => 'string'
            ],
            [
                'prop' => 'quote_id',
                'name' => 'quote',
                'type' => 'uuid',
                'model' => 'quote'
            ],
            [
                'prop' => 'date',
                'name' => 'date',
                'type' => 'date'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'orderstatus'
            ],
            [
                'prop' => 'total_price',
                'name' => 'total price (EUR)',
                'format' => 'in_euro',
                'filter_name' => 'total price',
                'type' => 'decimal',
                'children' => ['total_price_usd', 'customer_total_price']
            ],
            [
                'prop' => 'total_price_usd',
                'name' => 'total price (USD)',
                'format' => 'in_dollar',
                'type' => 'decimal',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'customer_total_price',
                'name' => 'Total Price (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'total_price'
            ],
        ],
        'default' => ['project', 'number', 'quote_id', 'date', 'status', 'total_price', 'total_price_usd', 'customer_total_price']
    ],
    'invoices' => [
        'all' => [
            [
                'prop' => 'intra_company',
                'name' => 'Intra Company',
                'type' => 'enum',
                'enum' => 'projecttype',
                'filterable' => 'invisible',
                'cast' => 'boolean',
            ],
            [
                'prop' => 'project',
                'name' => 'project',
                'type' => 'string'
            ],
            [
                'prop' => 'number',
                'name' => 'number',
                'type' => 'string'
            ],
            [
                'prop' => 'order_id',
                'name' => 'order',
                'type' => 'uuid',
                'model' => 'order',
                'cast' => 'order'
            ],
            [
                'prop' => 'customer_id',
                'name' => 'customer',
                'type' => 'uuid',
                'model' => 'customer'
            ],
            [
                'prop' => 'contact_id',
                'name' => 'contact',
                'type' => 'uuid',
                'model' => 'contact'
            ],
            [
                'prop' => 'date',
                'name' => 'date',
                'type' => 'date'
            ],
            [
                'prop' => 'due_date',
                'name' => 'due date',
                'type' => 'date'
            ],
            [
                'prop' => 'close_date',
                'name' => 'close date',
                'type' => 'date'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'invoicestatus'
            ],
            [
                'prop' => 'pay_date',
                'name' => 'pay date',
                'type' => 'date'
            ],
            [
                'prop' => 'reference',
                'name' => 'reference',
                'type' => 'string'
            ],
            [
                'prop' => 'currency_code',
                'name' => 'currency code',
                'type' => 'enum',
                'enum' => 'currencycode'
            ],
            [
                'prop' => 'total_price',
                'name' => 'total price (EUR)',
                'filter_name' => 'total price',
                'type' => 'decimal',
                'format' => 'in_euro',
                'children' => ['total_price_usd', 'customer_total_price']
            ],
            [
                'prop' => 'total_price_usd',
                'name' => 'total price (USD)',
                'type' => 'decimal',
                'format' => 'in_dollar',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'customer_total_price',
                'name' => 'Total Price (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'currency_rate_customer',
                'name' => 'conversion rate',
                'type' => 'decimal',
                'format' => 'number',
            ],
            [
                'prop' => 'legal_entity_id',
                'name' => 'legal entity',
                'type' => 'uuid',
                'model' => 'legal_entity'
            ],
            [
                'prop' => 'intra_company',
                'name' => 'intra_company',
                'type' => 'boolean',
                'hidden' => true
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date'
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date'
            ],
            [
                'prop' => 'details',
                'name' => 'details',
                'type' => 'object'
            ]
        ],
        'default' => [
            'project', 'number', 'order_id', 'customer_id', 'contact_id', 'date', 'due_date', 'status', 'details', 'total_price', 'total_price_usd', 'customer_total_price'
        ],
        'default_filter' => [
            [
                'prop' => 'status',
                'type' => 'enum',
                'value' => [
                    InvoiceStatus::draft()->getIndex(),
                    InvoiceStatus::approval()->getIndex(),
                    InvoiceStatus::submitted()->getIndex(),
                    InvoiceStatus::authorised()->getIndex(),
                    InvoiceStatus::paid()->getIndex(),
                    InvoiceStatus::partial_paid()->getIndex()
                ]
            ]
        ],
        'custom_filters' => [
            'overdue' => [
                [
                    'prop' => 'due_date',
                    'type' => 'date',
                    'value' => [
                        null,
                        null
                    ]
                ],
                [
                    'prop' => 'pay_date',
                    'type' => 'custom',
                    'value' => [
                        null
                    ]
                ],
                [
                    'prop' => 'status',
                    'type' => 'enum',
                    'value' => [
                        InvoiceStatus::submitted()->getIndex(),
                        InvoiceStatus::partial_paid()->getIndex()
                    ]
                ],
            ]
        ],
    ],
    'resource_invoices' => [
        'all' => [
            [
                'prop' => 'project',
                'name' => 'project',
                'type' => 'string'
            ],
            [
                'prop' => 'number',
                'name' => 'number',
                'type' => 'string'
            ],
            [
                'prop' => 'purchase_order_id',
                'name' => 'purchase order',
                'type' => 'uuid',
                'model' => 'purchase_order',
                'cast' => 'purchase_order',
            ],
            [
                'prop' => 'resource_id',
                'name' => 'resource',
                'type' => 'uuid',
                'model' => 'resource'
            ],
            [
                'prop' => 'order_id',
                'name' => 'order',
                'type' => 'uuid',
                'model' => 'order',
                'cast' => 'order'
            ],
            [
                'prop' => 'customer_id',
                'name' => 'customer',
                'type' => 'uuid',
                'model' => 'customer'
            ],
            [
                'prop' => 'contact_id',
                'name' => 'contact',
                'type' => 'uuid',
                'model' => 'contact'
            ],
            [
                'prop' => 'date',
                'name' => 'date',
                'type' => 'date'
            ],
            [
                'prop' => 'due_date',
                'name' => 'due date',
                'type' => 'date'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'invoicestatus'
            ],
            [
                'prop' => 'pay_date',
                'name' => 'pay date',
                'type' => 'date'
            ],
            [
                'prop' => 'reference',
                'name' => 'reference',
                'type' => 'string'
            ],
            [
                'prop' => 'currency_code',
                'name' => 'currency code',
                'type' => 'enum',
                'enum' => 'currencycode'
            ],
            [
                'prop' => 'total_price',
                'name' => 'total price (EUR)',
                'filter_name' => 'total price',
                'type' => 'decimal',
                'format' => 'in_euro',
                'children' => ['total_price_usd', 'customer_total_price']
            ],
            [
                'prop' => 'total_price_usd',
                'name' => 'total price (USD)',
                'type' => 'decimal',
                'format' => 'in_dollar',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'customer_total_price',
                'name' => 'Total Price (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'legal_entity_id',
                'name' => 'legal entity',
                'type' => 'uuid',
                'model' => 'legal_entity'
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date'
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date'
            ],
        ],
        'default' => [
            'project', 'number', 'order_id', 'customer_id', 'contact_id', 'date', 'due_date', 'status', 'purchase_order_id', 'resource_id', 'total_price', 'total_price_usd', 'customer_total_price'
        ],
        'default_filter' => [
            [
                'prop' => 'status',
                'type' => 'enum',
                'value' => [
                    InvoiceStatus::draft()->getIndex(),
                    InvoiceStatus::approval()->getIndex(),
                    InvoiceStatus::submitted()->getIndex(),
                    InvoiceStatus::authorised()->getIndex(),
                    InvoiceStatus::paid()->getIndex(),
                    InvoiceStatus::partial_paid()->getIndex()
                ]
            ]
        ]
    ],
    'project_invoices' => [
        'all' => [
            [
                'prop' => 'project',
                'name' => 'project',
                'type' => 'string'
            ],
            [
                'prop' => 'number',
                'name' => 'number',
                'type' => 'string'
            ],
            [
                'prop' => 'order_id',
                'name' => 'order',
                'type' => 'uuid',
                'model' => 'order',
                'cast' => 'order',
            ],
            [
                'prop' => 'date',
                'name' => 'date',
                'type' => 'date'
            ],
            [
                'prop' => 'due_date',
                'name' => 'due date',
                'type' => 'date'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'invoicestatus'
            ],
            [
                'prop' => 'pay_date',
                'name' => 'pay date',
                'type' => 'date'
            ],
            [
                'prop' => 'total_price',
                'name' => 'total price (EUR)',
                'format' => 'in_euro',
                'filter_name' => 'total price',
                'type' => 'decimal',
                'children' => ['total_price_usd', 'customer_total_price']
            ],
            [
                'prop' => 'total_price_usd',
                'name' => 'total price (USD)',
                'format' => 'in_dollar',
                'type' => 'decimal',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'customer_total_price',
                'name' => 'Total Price (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'customer_id',
                'name' => 'customer',
                'type' => 'uuid',
                'model' => 'customer'
            ],
        ],
        'default' => ['project', 'number', 'order_id', 'customer_id', 'date', 'due_date', 'status', 'pay_date', 'total_price', 'total_price_usd', 'customer_total_price']
    ],
    'purchase_orders' => [
        'all' => [
            [
                'prop' => 'intra_company',
                'name' => 'Intra Company',
                'type' => 'enum',
                'enum' => 'projecttype',
                'filterable' => 'invisible',
                'cast' => 'boolean',
            ],
            [
                'prop' => 'project',
                'name' => 'project',
                'type' => 'string'
            ],
            [
                'prop' => 'number',
                'name' => 'number',
                'type' => 'string'
            ],
            [
                'prop' => 'order_id',
                'name' => 'order',
                'type' => 'uuid',
                'model' => 'order',
                'cast' => 'order'
            ],
            [
                'prop' => 'customer_id',
                'name' => 'customer',
                'type' => 'uuid',
                'model' => 'customer'
            ],
            [
                'prop' => 'contact_id',
                'name' => 'contact',
                'type' => 'uuid',
                'model' => 'contact'
            ],
            [
                'prop' => 'resource_id',
                'name' => 'resource',
                'type' => 'uuid',
                'model' => 'resource'
            ],
            [
                'prop' => 'date',
                'name' => 'date',
                'type' => 'date'
            ],
            [
                'prop' => 'delivery_date',
                'name' => 'delivery date',
                'type' => 'date'
            ],
            [
                'prop' => 'pay_date',
                'name' => 'pay date',
                'type' => 'date'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'purchaseorderstatus'
            ],
            [
                'prop' => 'reference',
                'name' => 'reference',
                'type' => 'string'
            ],
            [
                'prop' => 'reason_of_rejection',
                'name' => 'reason of rejection',
                'type' => 'string'
            ],
            [
                'prop' => 'reason_of_penalty',
                'name' => 'reason of penalty',
                'type' => 'string'
            ],
            [
                'prop' => 'currency_code',
                'name' => 'currency code',
                'type' => 'enum',
                'enum' => 'currencycode'
            ],
            [
                'prop' => 'total_price',
                'name' => 'total price (EUR)',
                'filter_name' => 'total price',
                'type' => 'decimal',
                'format' => 'in_euro',
                'children' => ['total_price_usd', 'customer_total_price']
            ],
            [
                'prop' => 'total_price_usd',
                'name' => 'total price (USD)',
                'type' => 'decimal',
                'format' => 'in_dollar',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'customer_total_price',
                'name' => 'Total Price (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'legal_entity_id',
                'name' => 'legal entity',
                'type' => 'uuid',
                'model' => 'legal_entity'
            ],
            [
                'prop' => 'intra_company',
                'name' => 'intra_company',
                'type' => 'boolean',
                'hidden' => true
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date'
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date'
            ]
        ],
        'default' => [
            'project', 'number', 'order_id', 'customer_id', 'contact_id', 'resource_id', 'date', 'delivery_date', 'status', 'total_price', 'total_price_usd', 'customer_total_price'
        ],
        'default_filter' => [
            [
                'prop' => 'status',
                'type' => 'enum',
                'value' => [
                    PurchaseOrderStatus::draft()->getIndex(),
                    PurchaseOrderStatus::submitted()->getIndex(),
                    PurchaseOrderStatus::authorised()->getIndex(),
                    PurchaseOrderStatus::billed()->getIndex()
                ]
            ]
        ]
    ],
    'project_purchase_orders' => [
        'all' => [
            [
                'prop' => 'project',
                'name' => 'project',
                'type' => 'string'
            ],
            [
                'prop' => 'number',
                'name' => 'number',
                'type' => 'string'
            ],
            [
                'prop' => 'resource_id',
                'name' => 'resource',
                'type' => 'uuid',
                'model' => 'resource'
            ],
            [
                'prop' => 'date',
                'name' => 'date',
                'type' => 'date'
            ],
            [
                'prop' => 'delivery_date',
                'name' => 'delivery date',
                'type' => 'date'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'purchaseorderstatus'
            ],
            [
                'prop' => 'total_price',
                'name' => 'total price (EUR)',
                'format' => 'in_euro',
                'filter_name' => 'total price',
                'type' => 'decimal',
                'children' => ['total_price_usd', 'customer_total_price']
            ],
            [
                'prop' => 'total_price_usd',
                'name' => 'total price (USD)',
                'format' => 'in_dollar',
                'type' => 'decimal',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'customer_total_price',
                'name' => 'Total Price (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'details',
                'name' => 'details',
                'type' => 'object',
            ],
        ],
        'default' => ['project', 'number', 'resource_id', 'date', 'delivery_date', 'status', 'total_price', 'details', 'total_price_usd', 'customer_total_price']
    ],
    'invoice_payments' => [
        'all' => [
            [
                'prop' => 'number',
                'name' => 'number',
                'type' => 'string'
            ],
            [
                'prop' => 'order_id',
                'name' => 'order',
                'type' => 'uuid',
                'model' => 'order',
                'cast' => 'order',
            ],
            [
                'prop' => 'invoice_id',
                'name' => 'invoice',
                'type' => 'uuid',
                'model' => 'invoice'
            ],
            [
                'prop' => 'pay_date',
                'name' => 'pay date',
                'type' => 'date'
            ],
            [
                'prop' => 'pay_amount',
                'name' => 'amount paid',
                'type' => 'decimal',
                'format' => 'currency_code',
            ],
            [
                'prop' => 'currency_code',
                'name' => 'currency code',
                'type' => 'enum',
                'enum' => 'currencycode'
            ],
        ],
        'default' => ['number', 'order_id', 'invoice_id', 'pay_date', 'pay_amount']
    ],
    'external_access_purchase_orders' => [
        'all' => [
            [
                'prop' => 'number',
                'name' => 'number',
                'type' => 'string'
            ],
            [
                'prop' => 'resource_id',
                'name' => 'resource',
                'type' => 'uuid',
                'model' => 'resource'
            ],
            [
                'prop' => 'date',
                'name' => 'date',
                'type' => 'date'
            ],
            [
                'prop' => 'delivery_date',
                'name' => 'delivery date',
                'type' => 'date'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'purchaseorderstatus'
            ],
            [
                'prop' => 'total_price',
                'name' => 'total price',
                'type' => 'decimal'
            ],
            [
                'prop' => 'details',
                'name' => 'details',
                'type' => 'object',
            ],
            [
                'prop' => 'company_id',
                'name' => 'company',
                'type' => 'uuid',
                'model' => 'company',
            ],
        ],
        'default' => [
            'number',
            'resource_id',
            'date',
            'delivery_date',
            'status',
            'total_price',
            'details',
        ],
    ],
    'services' => [
        'all' => [
            [
                'prop' => 'name',
                'name' => 'name',
                'type' => 'string',
            ],
            [
                'prop' => 'price',
                'name' => 'price',
                'type' => 'decimal',
            ],
            [
                'prop' => 'price_unit',
                'name' => 'unit',
                'type' => 'string',
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date',
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date',
            ],
        ],
        'default' => [
            'name',
            'price',
            'price_unit',
        ],
    ],
    'project_resource_invoices' => [
        'all' => [
            [
                'prop' => 'number',
                'name' => 'number',
                'type' => 'string'
            ],
            [
                'prop' => 'resource_id',
                'name' => 'resource',
                'type' => 'uuid',
                'model' => 'resource'
            ],
            [
                'prop' => 'purchase_order_id',
                'name' => 'purchase order',
                'type' => 'uuid',
                'model' => 'purchase_order'
            ],
            [
                'prop' => 'date',
                'name' => 'date',
                'type' => 'date'
            ],
            [
                'prop' => 'due_date',
                'name' => 'due date',
                'type' => 'date'
            ],
            [
                'prop' => 'status',
                'name' => 'status',
                'type' => 'enum',
                'enum' => 'invoicestatus'
            ],
            [
                'prop' => 'total_price',
                'name' => 'total price (EUR)',
                'format' => 'in_euro',
                'filter_name' => 'total price',
                'type' => 'decimal',
                'children' => ['total_price_usd', 'customer_total_price']
            ],
            [
                'prop' => 'total_price_usd',
                'name' => 'total price (USD)',
                'format' => 'in_dollar',
                'type' => 'decimal',
                'parent' => 'total_price'
            ],
            [
                'prop' => 'customer_total_price',
                'name' => 'Total Price (Customer)',
                'type' => 'decimal',
                'format' => 'currency_code',
                'parent' => 'total_price'
            ],
        ],
        'default' => ['number', 'resource_id', 'purchase_order_id', 'date', 'due_date', 'status', 'total_price', 'total_price_usd', 'customer_total_price']
    ],
    'resource_services' => [
        'all' => [
            [
                'prop' => 'name',
                'name' => 'name',
                'type' => 'string',
            ],
            [
                'prop' => 'price',
                'name' => 'price',
                'type' => 'decimal',
            ],
            [
                'prop' => 'price_unit',
                'name' => 'unit',
                'type' => 'string',
            ],
            [
                'prop' => 'resource_id',
                'name' => 'resource',
                'type' => 'uuid',
                'model' => 'resource',
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date',
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date',
            ],
        ],
        'default' => [
            'name',
            'price',
            'price_unit'
        ],
    ],
    'legal_entities' => [
        'all' => [
            [
                'prop' => 'name',
                'name' => 'name',
                'type' => 'string',
            ],
            [
                'prop' => 'vat_number',
                'name' => 'vat number',
                'type' => 'string',
            ],
            [
                'prop' => 'addressline_1',
                'name' => 'address line 1',
                'type' => 'string',
            ],
            [
                'prop' => 'addressline_2',
                'name' => 'address line 2',
                'type' => 'string',
            ],
            [
                'prop' => 'city',
                'name' => 'city',
                'type' => 'string',
            ],
            [
                'prop' => 'region',
                'name' => 'region',
                'type' => 'string',
            ],
            [
                'prop' => 'postal_code',
                'name' => 'postal code',
                'type' => 'string',
            ],
            [
                'prop' => 'country',
                'name' => 'country',
                'type' => 'enum',
                'enum' => 'country',
            ],
            [
                'prop' => 'european_bank_name',
                'name' => 'european bank name',
                'type' => 'string',
            ],
            [
                'prop' => 'european_bank_iban',
                'name' => 'iban',
                'type' => 'string',
            ],
            [
                'prop' => 'european_bank_bic',
                'name' => 'bic',
                'type' => 'string',
            ],
            [
                'prop' => 'american_bank_name',
                'name' => 'american bank name',
                'type' => 'string',
            ],
            [
                'prop' => 'american_bank_account_number',
                'name' => 'account number',
                'type' => 'string',
            ],
            [
                'prop' => 'american_bank_routing_number',
                'name' => 'routing number',
                'type' => 'string',
            ],
            [
                'prop' => 'european_bank_addressline_1',
                'name' => 'european bank address line 1',
                'type' => 'string',
            ],
            [
                'prop' => 'european_bank_addressline_2',
                'name' => 'european bank address line 2',
                'type' => 'string',
            ],
            [
                'prop' => 'european_bank_city',
                'name' => 'european bank city',
                'type' => 'string',
            ],
            [
                'prop' => 'european_bank_region',
                'name' => 'european bank region',
                'type' => 'string',
            ],
            [
                'prop' => 'european_bank_postal_code',
                'name' => 'european bank postal code',
                'type' => 'string',
            ],
            [
                'prop' => 'european_bank_country',
                'name' => 'european bank country',
                'type' => 'enum',
                'enum' => 'country',
            ],
            [
                'prop' => 'american_bank_addressline_1',
                'name' => 'american bank address line 1',
                'type' => 'string',
            ],
            [
                'prop' => 'american_bank_addressline_2',
                'name' => 'american bank address line 2',
                'type' => 'string',
            ],
            [
                'prop' => 'american_bank_city',
                'name' => 'american bank city',
                'type' => 'string',
            ],
            [
                'prop' => 'american_bank_region',
                'name' => 'american bank region',
                'type' => 'string',
            ],
            [
                'prop' => 'american_bank_postal_code',
                'name' => 'american bank postal code',
                'type' => 'string',
            ],
            [
                'prop' => 'american_bank_country',
                'name' => 'american bank country',
                'type' => 'enum',
                'enum' => 'country',
            ],
            [
                'prop' => 'deleted_at',
                'name' => 'deleted at',
                'type' => 'date',
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date',
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date',
            ],
        ],
        'default' => [
            'name',
            'vat_number',
            'european_bank_name',
            'european_bank_iban',
            'european_bank_bic',
            'american_bank_name',
            'american_bank_account_number',
            'american_bank_routing_number',
        ],
    ],
    'employee_histories' => [
        'all' => [
            [
                'prop'  => 'employee_salary',
                'name'  => 'salary',
                'type'  => 'decimal',
            ],
            [
                'prop'  => 'working_hours',
                'name'  => 'working hours',
                'type'  => 'integer',
            ],
            [
                'prop'  => 'salary',
                'name'  => 'salary in EUR',
                'type'  => 'in_euro',
            ],
            [
                'prop'  => 'salary_usd',
                'name'  => 'salary in USD',
                'type'  => 'in_dollar',
            ],
            [
                'prop'  => 'start_date',
                'name'  => 'start date',
                'type'  => 'date',
            ],
            [
                'prop'  => 'end_date',
                'name'  => 'end date',
                'type'  => 'date',
            ],
            [
                'prop' => 'default_currency',
                'name' => 'currency',
                'type' => 'enum',
                'enum' => 'currencycode'
            ],
            [
                'prop' => 'created_at',
                'name' => 'created at',
                'type' => 'date',
            ],
            [
                'prop' => 'updated_at',
                'name' => 'updated at',
                'type' => 'date',
            ],
        ],
        'default' => [
            'employee_salary',
            'working_hours',
            'default_currency',
            'start_date',
            'end_date',
        ],
    ],
    'company_rents' => [
        'all' => [
            [
                'prop' => 'author_id',
                'name' => 'Author',
                'type' => 'uuid',
                'model' => 'user',
            ],
            [
                'prop' => 'amount',
                'name' => 'Amount',
                'type' => 'decimal',
            ],
            [
                'prop' => 'admin_amount',
                'name' => 'Admin Amount',
                'type' => 'decimal',
            ],
            [
                'prop' => 'name',
                'name' => 'Name',
                'type' => 'string',
            ],
            [
                'prop' => 'start_date',
                'name' => 'Start date',
                'type' => 'date',
            ],
            [
                'prop' => 'end_date',
                'name' => 'End date',
                'type' => 'date',
            ],
            [
                'prop' => 'created_at',
                'name' => 'Created at',
                'type' => 'date',
            ],
            [
                'prop' => 'deleted_at',
                'name' => 'Deleted at',
                'type' => 'date',
            ],
        ],
        'default' => [
            'author_id', 'amount', 'name', 'start_date', 'end_date', 'created_at', 'deleted_at'
        ],
    ],
    'smtp_settings' => [
        'all' => [
            [
                'prop' => 'sender_name',
                'name' => 'Sender',
                'type' => 'string',
            ],
            [
                'prop' => 'sender_email',
                'name' => 'Email',
                'type' => 'string',
            ]
        ],
        'default' => ['sender_name', 'sender_email'],
    ],
    'email_templates' => [
        'all' => [
            [
                'prop' => 'title',
                'name' => 'Title',
                'type' => 'string',
            ],
            [
                'prop' => 'created_at',
                'name' => 'Created at',
                'type' => 'date',
            ],
            [
                'prop' => 'default',
                'name' => 'Default',
                'type' => 'boolean',
            ],
        ],
        'default' => ['title', 'created_at', 'default'],
    ],
    'design_templates' => [
        'all' => [
            [
                'prop' => 'title',
                'name' => 'Title',
                'type' => 'string',
            ],
            [
                'prop' => 'created_at',
                'name' => 'Created at',
                'type' => 'date',
            ],
        ],
        'default' => ['title', 'created_at'],
    ],
    'project_sales_commission_percentages' => [
        'all' => [
            [
                'prop' => 'sales_person_id',
                'name' => 'Sale Person',
                'type' => 'uuid',
                'model' => 'user',
            ],
            [
                'prop' => 'sales_person',
                'name' => 'Sale Person Name',
                'type' => 'string',
            ],
            [
                'prop' => 'base_commission',
                'name' => 'Base Commission',
                'type' => 'percentage',
            ],
            [
                'prop' => 'current_commission_model',
                'name' => 'Current Commission Model',
                'type' => 'string'
            ],
            [
                'prop' => 'total_commission',
                'name' => 'Total Commission',
                'type' => 'decimal',
            ],
            [
                'prop' => 'nb_commission',
                'name' => 'Nb Commission',
                'type' => 'integer',
            ],
            [
                'prop' => 'details',
                'name' => 'details',
                'type' => 'object',
            ],
        ],
        'default' => [
            'sales_person_id', 'base_commission', 'total_commission', 'nb_commission', 'details'
        ],
    ],
];
