<?php
declare(strict_types=1);

namespace App\Entities;

use ManaPHP\Persistence\Attribute\PrimaryKey;

#[PrimaryKey('login_id')]
class AdminLoginLog extends Entity
{
    public int $login_id;
    public int $admin_id;
    public string $admin_name;
    public string $client_ip;
    public string $client_udid;
    public string $user_agent;
    public int $created_time;
}