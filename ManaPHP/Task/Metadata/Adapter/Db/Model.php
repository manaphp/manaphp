<?php
namespace ManaPHP\Task\Metadata\Adapter\Db;

/**
 * Class ManaPHP\Task\Metadata\Adapter\Db\Model
 *
 * @package tasksMetadata\adapter
 */
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

    public function getSource($context = null)
    {
        return 'manaphp_task_metadata';
    }
}