<?php

namespace ManaPHP\Http\Request\File;

class Exception extends \ManaPHP\Http\Request\Exception
{
    public function getStatusCode()
    {
        return 400;
    }
}