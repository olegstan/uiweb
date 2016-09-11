<?php
return [
    'debug' => true,

    'log' => true,

    //defaults
    'connection' => 'mysql',

    'services' => [
        'http' => [
            Uiweb\Http\HttpServiceProvider::class
        ],
        'console' => [
            Uiweb\Console\ConsoleServiceProvider::class
        ]
    ]
];