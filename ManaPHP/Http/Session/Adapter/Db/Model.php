<?php
namespace ManaPHP\Http\Session\Adapter\Db;

/**
 * Class ManaPHP\Http\Session\Adapter\Db\Model
 *
 * @package session\adapter
 */
class Model extends \ManaPHP\Mvc\Model
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

    /**
     * @return string
     */
    public function getSource()
    {
        return 'manaphp_session';
    }
}