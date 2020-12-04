<?php

namespace ManaPHP\Data\Query;

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

    /**
     * @return array
     */
    public function getJson()
    {
        return ['code' => 404, 'message' => 'record is not exists'];
    }
}