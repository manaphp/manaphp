<?php
declare(strict_types=1);

namespace App\Models;

class AdminActionLog extends Model
{
    public int $id;
    public int $admin_id;
    public string $admin_name;
    public string $method;
    public string $handler;
    public int $tag;
    public string $url;
    public string $data;
    public string $client_ip;
    public string $client_udid;
    public int $created_time;
}
