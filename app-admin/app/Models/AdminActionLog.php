<?php
declare(strict_types=1);

namespace App\Models;

class AdminActionLog extends Model
{
    public $id;
    public $admin_id;
    public $admin_name;
    public $method;
    public $path;
    public $tag;
    public $url;
    public $data;
    public $client_ip;
    public $client_udid;
    public $created_time;

    public function table(): string
    {
        return 'admin_action_log';
    }
}
