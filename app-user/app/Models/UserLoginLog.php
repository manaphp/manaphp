<?php

namespace App\Models;

class UserLoginLog extends Model
{
    public $login_id;
    public $user_id;
    public $user_name;
    public $client_ip;
    public $client_udid;
    public $user_agent;
    public $created_time;

    public function primaryKey()
    {
        return 'login_id';
    }
}