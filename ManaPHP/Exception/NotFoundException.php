<?php

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class NotFoundException extends Exception
{
    /**
     * @return int
     */
    public function getStatusCode()
    {
        return 404;
    }
}