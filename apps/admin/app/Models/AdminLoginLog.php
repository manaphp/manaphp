<?php

namespace App\Models;

/**
 * Class App\Models\AdminLoginLog
 */
class AdminLoginLog extends Model
{
    public $login_id;
    public $admin_id;
    public $admin_name;
    public $client_ip;
    public $client_udid;
    public $user_agent;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'admin_login_log';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'login_id';
    }
}