<?php
declare(strict_types=1);

namespace App\Entities;

use ManaPHP\Persistence\Attribute\Id;

class UserLoginLog extends Entity
{
    #[Id]
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