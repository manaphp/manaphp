<?php

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class ForbiddenException extends Exception
{
    public function getStatusCode()
    {
        return 403;
    }
}