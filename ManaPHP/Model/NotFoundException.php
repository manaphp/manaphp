<?php
namespace ManaPHP\Model;

class NotFoundException extends \ManaPHP\Model\Exception
{
    /**
     * @var string
     */
    public $model;

    /**
     * @var int|string|array
     */
    public $filters;
}