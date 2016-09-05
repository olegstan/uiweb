<?php
namespace Framework\Text;

class Charset
{
    public $encodings = [
        'utf-8',
        'windows-1251'
    ];

    public static function isUtf8($string)
    {
        return (mb_detect_encoding($string, 'utf-8', true) === 'utf-8');
    }

    public static function isWindows1251($string)
    {
        return (mb_detect_encoding($string, 'windows-1251', true) === 'windows-1251');
    }
}