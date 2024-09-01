<?php

return [
    'seeding' => false,

    'taxes' => [
        'ireland' => 21,
    ],

    'price_modifiers' => [
        [
            'description' => 'Special Discount',
            'type' => 0,
            'quantity_type' => [0, 1],
            'max_field_value'=>'special_discount_max_value'
        ],
        [
            'description' => 'Project Management',
            'type' => 1,
            'quantity_type' => 0,
            'max_field_value'=>'project_management_max_value'
        ],
        [
            'description' => 'Director Fee',
            'type' => 1,
            'quantity_type' => 0,
            'max_field_value'=>'director_fee_max_value'
        ],
        [
            'description' => 'Transaction Fee',
            'type' => 1,
            'quantity_type' => 0,
            'max_field_value'=>'transaction_fee_max_value'
        ],
    ],
];
