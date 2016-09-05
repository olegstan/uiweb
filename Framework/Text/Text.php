<?php
namespace core\helper;

class Text
{
    protected static $encoding = 'utf-8';
    protected static $line_breaks = ["\r\n", "\r", "\n"];

    public static function previewByCharacters($text, $max_characters = 100)
    {
        $text = static::normalizeLinebreaks($text);

        // this prevents the breaking of the &quote; etc
        $text = html_entity_decode($text, ENT_QUOTES, static::$encoding);

        if (mb_strlen($text, static::$encoding) > $max_characters) {
            $text = self::limitCharacters($text, $max_characters);
        }

        return html_entity_decode($text, ENT_QUOTES, static::$encoding);
    }

    protected static function normalizeLinebreaks($text)
    {
        return str_replace(static::$line_breaks, "\n", $text);
    }

    protected static function previewLines($text, $max_lines = 10)
    {
        $lines = explode("\n", $text);
        $limited_lines = array_slice($lines, 0, $max_lines = 10);

        return implode("\n", $limited_lines);
    }

    protected static function limitCharacters($text, $max_characters)
    {
        return mb_substr($text, 0, $max_characters, static::$encoding);
    }



    public static function toUtf8($text){
        return iconv(mb_detect_encoding($text, mb_detect_order(), true), static::$encoding, $text);
    }

    public static function strlenUtf8($text)
    {
        mb_strlen($text, static::$encoding);
    }
}