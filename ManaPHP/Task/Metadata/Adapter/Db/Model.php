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

    public function getSource()
    {
        return 'manaphp_task_metadata';
    }
}