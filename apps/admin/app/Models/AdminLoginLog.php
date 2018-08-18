<?php
namespace App\Models;

/**
 * Class AdminLogin
 *
 * @package App\Models
 *
 * @method static $this findFirstByLoginId($id)
 */
class AdminLoginLog extends ModelBase
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
    public $admin_name;

    /**
     * @var string
     */
    public $client_ip;

    /**
     * @var string
     */
    public $client_udid;

    /**
     * @var string
     */
    public $user_agent;

    /**
     * @var int
     */
    public $created_time;
}