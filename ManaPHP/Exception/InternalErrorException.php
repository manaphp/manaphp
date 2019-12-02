<?php
namespace ManaPHP\Exception;

use ManaPHP\Exception;

class InternalErrorException extends Exception
{
    public function __construct($message = '内部错误，请稍后重试')
    {
        parent::__construct($message);

        $this->_json = [
            'code' => 500,
            'message' => $this->getMessage()
        ];
    }
}