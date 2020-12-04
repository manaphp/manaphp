<?php

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class UnauthorizedException extends Exception
{
    /**
     * @return int
     */
    public function getStatusCode()
    {
        return 401;
    }
}