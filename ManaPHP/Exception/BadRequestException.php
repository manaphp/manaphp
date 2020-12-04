<?php

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class BadRequestException extends Exception
{
    /**
     * @return int
     */
    public function getStatusCode()
    {
        return 400;
    }
}