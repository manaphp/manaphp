<?php
namespace ManaPHP\Store\Adapter\Db;

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

    public function getSource()
    {
        return 'manaphp_store';
    }
}