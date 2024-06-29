<?php
declare(strict_types=1);

namespace App\Entities;

use ManaPHP\Persistence\Attribute\Id;

class UserLoginLog extends Entity
{
    #[Id]
    public int $login_id;

    public int $user_id;
    public string $user_name;
    public string $client_ip;
    public string $client_udid;
    public string $user_agent;
    public int $created_time;
}