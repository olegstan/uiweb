<?php
//получить доступные PDO драйвера сервера
//print_r(PDO::getAvailableDrivers());
//настройки подключения ДБ

return [
    /**
     * системные настройки
     */

    'protocol' => 'http', //протокол сервера




    /**
     * изображение по умолчанию
     */

    'default_image' => '/files/originals/default/none.png',


    'product_url_end' => '.html',

    'material_url_end' => '.html',




    /**
     * настройка для отображения цены
     *
     * если true то 100
     *
     * если false то 100.00
     */

    'without_nulls' => true,

    'defug' => true,

    'log' => true

];