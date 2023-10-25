<?php
declare(strict_types=1);

namespace App\Models;

class UserActionLog extends Model
{
    public $id;
    public $user_id;
    public $user_name;
    public $method;
    public $handler;
    public $tag;
    public $url;
    public $data;
    public $client_ip;
    public $client_udid;
    public $created_time;
}
