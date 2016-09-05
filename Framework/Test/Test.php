<?php
namespace Framework\Test;

class Test
{
    public function test()
    {
        //realize in children
    }

    public static function equal($a, $b)
    {
        if($a != $b ){
            throw new Exception( 'Subjects are not equal.' );
        }
    }
}