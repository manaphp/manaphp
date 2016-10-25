<?php
namespace ManaPHP\Cache\Adapter\Db;

/**
 * Class ManaPHP\Cache\Adapter\Db\Model
 *
 * @package cache\adapter
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

    /**
     * @var int
     */
    public $expired_time;

    /**
     * @return string
     */
    public function getSource()
    {
        return 'manaphp_cache';
    }
}