<?php
namespace ManaPHP\Exception;

use ManaPHP\Exception;

class RequiredValidatorException extends Exception
{
    /**
     * @var string
     */
    public $parameter_name;

    /**
     * RequiredValidatorException constructor.
     *
     * @param string $parameter_name
     */
    public function __construct($parameter_name)
    {
        $this->parameter_name = $parameter_name;

        parent::__construct(['missing required parameter: :parameter', 'parameter' => $parameter_name]);
    }
}