<?php
namespace ManaPHP\Exception;

use ManaPHP\Exception;

class BadRequestException extends Exception
{
    public function getStatusCode()
    {
        return 400;
    }

    public function getStatusText()
    {
        return 'Bad Request';
    }
}