<?php

namespace ManaPHP\Validating\Validator;

use ManaPHP\Exception;

class ValidateFailedException extends Exception
{
    /**
     * @var array
     */
    protected $_errors;

    /**
     * @param array           $errors
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct($errors, $code = 0, $previous = null)
    {
        $this->_errors = $errors;
        $this->_json = ['code' => 'validator.errors', 'message' => json_stringify($errors, JSON_PRETTY_PRINT)];

        parent::__construct(json_stringify($errors), $code, $previous);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    public function getStatusCode()
    {
        return 400;
    }
}