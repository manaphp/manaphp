<?php
namespace ManaPHP\Http\Session\Adapter\Db;

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

    public function getSource()
    {
        return 'manaphp_session';
    }
}