<?php
return [
    'debug' => true,

    'log' => true,

    //defaults
    'connection' => 'mysql',

    'services' => [
        'http' => [
            Uiweb\Auth\AuthServiceProvider::class
        ],
        'console' => [
            
        ]
    ]
];