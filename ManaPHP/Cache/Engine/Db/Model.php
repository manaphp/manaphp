<?php
namespace ManaPHP\Cache\Engine\Db;

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

    public function getSource()
    {
        return 'manaphp_cache';
    }
}