<?php
namespace ManaPHP\Task\Metadata\Adapter\Db;

class Model extends \ManaPHP\Mvc\Model
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $value;

    /**
     * @return string
     */
    public function getSource()
    {
        return 'manaphp_task_metadata';
    }
}