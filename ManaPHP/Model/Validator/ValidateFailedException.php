<?php

namespace ManaPHP\Model\Validator;

class ValidateFailedException extends \ManaPHP\Exception
{
    public function getStatusCode()
    {
        return 400;
    }
}