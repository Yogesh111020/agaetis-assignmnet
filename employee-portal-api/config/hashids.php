<?php

return [
    'default' => 'main',
    'connections' => [
        'main' => [
            'salt' => env('HASHIDS_SALT', env('APP_KEY')),
            'length' => 5,
        ],
    ],
];
