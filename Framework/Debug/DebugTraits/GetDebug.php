<?php
namespace Framework\Core\DebugTraits;


use Framework\Debug\Debug;

trait GetDebug
{
    public function getDebug()
    {
        return Debug::getInstance();
    }
}