<?php
namespace ManaPHP\Http\Session\Engine\Db;

/**
 * Class ManaPHP\Http\Session\Engine\Db\Model
 *
 * @package session\engine
 */
class Model extends \ManaPHP\Db\Model
{

    /**
     * @var int
     */
    public $session_id;

    /**
     * @var string
     */
    public $data;

    /**
     * @var int
     */
    public $expired_time;

    public static function getSource($context = null)
    {
        return 'manaphp_session';
    }
}