<?php
namespace Framework\Validation\ValidationTraits;

use Framework\Validation\Validation;

/**
 * Class ValidateionTrait
 * @package Framework\Validation\ValidationTraits
 */
trait ValidationTrait
{
    /**
     * @param null $scenario
     * @return Validation
     */
    public function validate($scenario = null)
    {
        return (new Validation($this->getRules($scenario)));
    }
}