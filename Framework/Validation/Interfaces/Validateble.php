<?php
namespace Framework\Validation\Interfaces;

interface Validateble
{
    public function validate($scenario = null);

    public function getRules($scenario = null);
}