<?php
namespace ManaPHP\Http\Session\Adapter\Db;

/**
 * Class ManaPHP\Http\Session\Adapter\Db\Model
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
     * @var int
     */
    public $user_id;

    /**
     * @var string
     */
    public $client_ip;

    /**
     * @var string
     */
    public $data;

    /**
     * @var int
     */
    public $updated_time;

    /**
     * @var int
     */
    public $expired_time;

    public function getSource($context = null)
    {
        return 'manaphp_session';
    }
}