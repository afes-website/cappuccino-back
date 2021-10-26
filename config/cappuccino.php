<?php
return [

    /*
    |--------------------------------------------------------------------------
    | GuestType List
    |--------------------------------------------------------------------------
    |
    | Used for GuestController
    | This restrict the WristBand Prefix
    */

    'guest_types' => [
        'GuestBlue' => [
            'prefix' => 'GB',
            'class' => 'General'
        ],
        'GuestRed' => [
            'prefix' => 'GR',
            'class' => 'General'
        ],
        'GuestYellow' => [
            'prefix' => 'GY',
            'class' => 'General'
        ],
        'GuestWhite' => [
            'prefix' => 'GW',
            'class' => 'General'
        ],
        'ParentPurple' => [
            'prefix' => 'PP',
            'class' => 'Parent'
        ],
        'StudentGray' => [
            'prefix' => 'SG',
            'class' => 'Student'
        ],
        'TestBlue' => [
            'prefix' => 'TB',
            'class' => 'General'
        ],
        'TestRed' => [
            'prefix' => 'TR',
            'class' => 'General'
        ],
        'TestYellow' => [
            'prefix' => 'TY',
            'class' => 'General'
        ]
    ]
];
