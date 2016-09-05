<?php
namespace Framework\Text;

class Translit
{
    public static function make($string, array $symbols  = [])
    {
        //merge symbols
        $converter = array_merge([
            'а' => 'a',   'б' => 'b',   'в' => 'v',
            'г' => 'g',   'д' => 'd',   'е' => 'e',
            'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
            'и' => 'i',   'й' => 'y',   'к' => 'k',
            'л' => 'l',   'м' => 'm',   'н' => 'n',
            'о' => 'o',   'п' => 'p',   'р' => 'r',
            'с' => 's',   'т' => 't',   'у' => 'u',
            'ф' => 'f',   'х' => 'h',   'ц' => 'ts',
            'ч' => 'ch',  'ш' => 'sh',  'щ' => 'shch',
            'ь' => '',  'ы' => 'i',   'ъ' => '',
            'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

            'А' => 'A',   'Б' => 'B',   'В' => 'V',
            'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
            'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
            'И' => 'I',   'Й' => 'Y',   'К' => 'K',
            'Л' => 'L',   'М' => 'M',   'Н' => 'N',
            'О' => 'O',   'П' => 'P',   'Р' => 'R',
            'С' => 'S',   'Т' => 'T',   'У' => 'U',
            'Ф' => 'F',   'Х' => 'H',   'Ц' => 'Ts',
            'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Shch',
            'Ь' => '\'',  'Ы' => 'I',   'Ъ' => '',
            'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',

            //cpecial chars
            ' ' => '', '.' => '.', '-' => '-',
            '?' => '', '!' => '', ',' => '', ';' => '',
            '"' => '', '\'' => '', '{' => '', '}' => '',
            '(' => '', ')' => '', ':' => '', '*' => '',
            '[' => '', ']' => '', '%' => '', '$' => '',
            '#' => '', '№' => '', '@' => '', '=' => '',
            '^' => '', '+' => '', '/' => '',
            '\\' => '', '|' => '',
        ], $symbols);

        //$string = preg_replace('/\-\-+/', '-', $string);
//        return trim(mb_strtolower(strtr($string, $converter)), 'utf-8');
        return trim(mb_strtolower(strtr($string, $converter), 'utf-8'));
    }

    public static function url()
    {

    }
}