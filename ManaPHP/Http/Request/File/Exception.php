<?php

namespace ManaPHP\Http\Request\File;

/**
 * Class ManaPHP\Http\Request\File\Exception
 *
 * @package request
 */
class Exception extends \ManaPHP\Http\Request\Exception
{
    public function getStatusCode()
    {
        return 400;
    }
}