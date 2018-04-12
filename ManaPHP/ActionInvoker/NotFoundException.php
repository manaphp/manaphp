<?php
namespace ManaPHP\ActionInvoker;

class NotFoundException extends \ManaPHP\Exception
{
    public function getStatusCode()
    {
        return 404;
    }
}