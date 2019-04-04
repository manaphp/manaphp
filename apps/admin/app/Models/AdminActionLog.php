<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class AdminActionLog
 * @package App\Models
 *
 * CREATE TABLE `admin_action_log` (
 * `id` int(11) NOT NULL AUTO_INCREMENT,
 * `user_id` int(11) NOT NULL,
 * `user_name` varchar(32) CHARACTER SET ascii NOT NULL,
 * `ip` char(15) CHARACTER SET ascii NOT NULL,
 * `udid` char(16) CHARACTER SET ascii NOT NULL DEFAULT '',
 * `path` varchar(32) CHARACTER SET ascii NOT NULL,
 * `method` varchar(15) CHARACTER SET ascii NOT NULL,
 * `url` varchar(128) NOT NULL,
 * `data` text NOT NULL,
 * `created_time` int(11) NOT NULL,
 * PRIMARY KEY (`id`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8
 *
 */
class AdminActionLog extends Model
{
    public $id;
    public $user_id;
    public $user_name;
    public $client_ip;
    public $client_udid;
    public $path;
    public $method;
    public $url;
    public $data;
    public $created_time;
}
