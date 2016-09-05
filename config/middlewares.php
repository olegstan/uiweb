<?php
return [
    //default middleware for all route
    'http' => [
        App\Middlewares\Authenticate::class,
        App\Middlewares\Locale::class
    ],
    'console' => []
];