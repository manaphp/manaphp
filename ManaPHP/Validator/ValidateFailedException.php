<?php

namespace ManaPHP\Validator;

use ManaPHP\Exception;

class ValidateFailedException extends Exception
{
    /**
     * @var array
     */
    protected $_errors;

    /**
     * ValidateFailedException constructor.
     *
     * @param array           $errors
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct($errors, $code = 0, \Exception $previous = null)
    {
        $this->_errors = $errors;
        $this->_json = ['code' => 'validate.errors', 'message' => json_encode($errors, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)];

        parent::__construct(json_encode($errors, JSON_UNESCAPED_UNICODE), $code, $previous);
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