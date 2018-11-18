<?php
namespace App\Models;

/**
 * Class AdminLogin
 *
 * @package App\Models
 *
 * @method static $this findFirstByLoginId($id)
 *
 * CREATE TABLE `admin_login_log` (
 * `login_id` int(11) NOT NULL AUTO_INCREMENT,
 * `admin_id` int(11) NOT NULL,
 * `admin_name` char(16) CHARACTER SET ascii NOT NULL,
 * `client_ip` char(15) CHARACTER SET ascii NOT NULL,
 * `client_udid` char(16) CHARACTER SET ascii NOT NULL,
 * `user_agent` char(128) CHARACTER SET ascii NOT NULL,
 * `created_time` int(11) NOT NULL,
 * PRIMARY KEY (`login_id`,`admin_id`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8
 *
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