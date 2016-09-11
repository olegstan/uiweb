<?php
namespace Uiweb\View;

class Modificator
{
    public function toMoney($number, $decimal = 2, $sign = '$')
    {
        return number_format($number, $decimal) . ' ' . $sign;
    }
}