<?php
namespace ManaPHP\Message\Queue\Adapter\Db;

/**
 * Class ManaPHP\Message\Queue\Adapter\Db\Model
 *
 * @package ManaPHP\Message\Queue\Adapter\Db
 */
class Model extends \ManaPHP\Mvc\Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var bool
     */
    public $deleted;

    /**
     * @var string
     */
    public $topic;

    /**
     * @var string
     */
    public $body;

    /**
     * @var int
     */
    public $priority;
    /**
     * @var int
     */
    public $created_time;

    /**
     * @return string
     */
    public function getSource()
    {
        return 'manaphp_message_queue';
    }
}