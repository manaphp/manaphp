<?php
namespace Application\Admin\Models;

/**
 * Class AdminLogin
 *
 * @package Application\Admin\Models
 *
 * @method static $this findFirstByLoginId($id)
 */
class AdminLogin extends ModelBase
{
    /**
     * @var int
     */
    public $login_id;

    /**
     * @var int
     */
    public $admin_id;

    /**
     * @var string
     */
    public $ip;

    /**
     * @var string
     */
    public $udid;

    /**
     * @var string
     */
    public $user_agent;

    /**
     * @var int
     */
    public $login_time;

    /**
     * @var int
     */
    public $logout_time;
}