<?php

namespace ManaPHP\Model\Validator;

class ValidateFailedException extends \ManaPHP\Exception
{
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        $this->_json = ['code' => 'model.validate.errors', 'message' => json_encode($message, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)];
        parent::__construct(json_encode($message), $code, $previous);
    }

    public function getStatusCode()
    {
        return 400;
    }
}