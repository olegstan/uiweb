<?php
namespace Framework\Core\CoreTraits;

use Framework\Core\Core;

/**
 * Class GetCore
 * @package Framework\Core
 */
trait GetCoreTrait
{

    /**
     * @return Core
     */

    public function getCore()
    {
        return Core::getCore();
    }
}