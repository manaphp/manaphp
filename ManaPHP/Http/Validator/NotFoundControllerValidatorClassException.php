<?php
namespace ManaPHP\Http\Validator;

class NotFoundControllerValidatorClassException extends Exception
{
    public function getStatusCode()
    {
        return 500;
    }
}