<?php
declare(strict_types=1);

namespace App\Models;

class AdminLoginLog extends Model
{
    public $login_id;
    public $admin_id;
    public $admin_name;
    public $client_ip;
    public $client_udid;
    public $user_agent;
    public $created_time;

    public function primaryKey(): string
    {
        return 'login_id';
    }
}