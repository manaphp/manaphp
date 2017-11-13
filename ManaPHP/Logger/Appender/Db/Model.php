<?php
namespace ManaPHP\Logger\Appender\Db;

/**
 * Class ManaPHP\Logger\Appender\Db\Model
 *
 * @package logger
 */
class Model extends \ManaPHP\Db\Model
{
    /**
     * @var int
     */
    public $log_id;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var string
     */
    public $user_name;

    /**
     * @var string
     */
    public $level;

    /**
     * @var string
     */
    public $category;

    /**
     * @var string
     */
    public $location;

    /**
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $ip;

    /**
     * @var int
     */
    public $created_time;

    public static function getSource($context = null)
    {
        return 'manaphp_log';
    }
}