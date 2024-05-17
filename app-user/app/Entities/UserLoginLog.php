<?php
declare(strict_types=1);

namespace App\Entities;

class UserLoginLog extends Entity
{
    public $login_id;
    public $user_id;
    public $user_name;
    public $client_ip;
    public $client_udid;
    public $user_agent;
    public $created_time;

    public function primaryKey(): string
    {
        return 'login_id';
    }
}