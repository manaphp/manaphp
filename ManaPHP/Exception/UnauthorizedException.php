<?php

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class UnauthorizedException extends Exception
{
    public function getStatusCode()
    {
        return 401;
    }
}