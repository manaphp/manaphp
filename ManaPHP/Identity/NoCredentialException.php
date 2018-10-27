<?php
namespace ManaPHP\Identity;

use ManaPHP\Exception\AuthenticationException;

class NoCredentialException extends AuthenticationException
{
    public function __construct($message = 'No Credentials', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
