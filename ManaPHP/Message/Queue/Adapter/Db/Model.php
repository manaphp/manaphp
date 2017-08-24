<?php
namespace ManaPHP\Message\Queue\Adapter\Db;

/**
 * Class ManaPHP\Message\Queue\Adapter\Db\Model
 *
 * @package messageQueue\adapter
 */
class Model extends \ManaPHP\Db\Model
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

    public static function getSource($context = null)
    {
        return 'manaphp_message_queue';
    }
}