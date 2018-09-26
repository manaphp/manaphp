<?php
namespace ManaPHP\Model;

class NotFoundException extends Exception
{
    /**
     * @var string
     */
    public $model;

    /**
     * @var int|string|array
     */
    public $filters;

    public function getStatusCode()
    {
        return 404;
    }
}