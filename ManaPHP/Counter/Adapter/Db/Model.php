<?php
namespace ManaPHP\Counter\Adapter\Db;

class Model extends \ManaPHP\Mvc\Model
{
    /**
     * @var string
     */
    public $hash;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $id;

    /**
     * @var int
     */
    public $value;

    /**
     * @return string
     */
    public function getSource()
    {
        return 'manaphp_counter';
    }
}