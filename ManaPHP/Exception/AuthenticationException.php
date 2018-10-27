<?php
namespace ManaPHP\Exception;

use ManaPHP\Exception;

class AuthenticationException extends Exception
{
    public function getStatusCode()
    {
        return 401;
    }
}