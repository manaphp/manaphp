<?php

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class ForbiddenException extends Exception
{
    /**
     * @return int
     */
    public function getStatusCode()
    {
        return 403;
    }
}