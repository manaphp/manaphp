<?php
namespace ManaPHP\Store\Adapter\Db;

/**
 * Class ManaPHP\Store\Adapter\Db\Model
 *
 * @package store\adapter
 */
class Model extends \ManaPHP\Mvc\Model
{
    /**
     * @var string
     */
    public $hash;

    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $value;

    public static function getSource($context = null)
    {
        return 'manaphp_store';
    }
}