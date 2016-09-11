<?php
return [
    /**
     * механизм хранения сессий
     *
     * 'files', 'mysql', 'redis'
     */
    'handler' => 'files',

    'hash' => [
        'cost' => '10', // цена алгоритмического расхода
        'salt' => 'a25jlfAsdaag42Asdasdaf', // соль хеширования
    ]
];