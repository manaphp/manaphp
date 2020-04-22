<?php

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class NotFoundException extends Exception
{
    public function getStatusCode()
    {
        return 404;
    }
}