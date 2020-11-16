<?php

namespace ManaPHP\Data\Query;

use ManaPHP\Exception;

class NotFoundException extends Exception
{
    public function getStatusCode()
    {
        return 404;
    }

    public function getJson()
    {
        return ['code' => 404, 'message' => 'record is not exists'];
    }
}