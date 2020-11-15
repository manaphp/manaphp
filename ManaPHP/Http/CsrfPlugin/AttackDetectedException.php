<?php

namespace ManaPHP\Plugins\CsrfPlugin;

use ManaPHP\Exception;

class AttackDetectedException extends Exception
{
    public function __construct($message = 'Possible CSRF attack detected')
    {
        parent::__construct($message);
    }

    public function getStatusCode()
    {
        return 400;
    }
}