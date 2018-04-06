<?php
namespace ManaPHP\Mvc;

/**
 * Class ManaPHP\Mvc\NotFoundException
 *
 * @package ManaPHP\Mvc
 */
class NotFoundException extends Exception
{
    public function getStatusCode()
    {
        return 404;
    }

    public function getStatusText()
    {
        return 'Not Found';
    }
}