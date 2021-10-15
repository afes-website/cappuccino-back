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
        'GuestPurple' => [
            'prefix' => 'GP',
            'class' => 'General'
        ],
        'GuestOrange' => [
            'prefix' => 'GO',
            'class' => 'General'
        ],
        'GuestGreen' => [
            'prefix' => 'GG',
            'class' => 'General'
        ],
        'GuestWhite' => [
            'prefix' => 'GW',
            'class' => 'General'
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
