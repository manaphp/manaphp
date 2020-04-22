<?php

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class BadRequestException extends Exception
{
    public function getStatusCode()
    {
        return 400;
    }
}