<?php

namespace App\Models;

class UserActionLog extends Model
{
    public $id;
    public $user_id;
    public $user_name;
    public $method;
    public $path;
    public $tag;
    public $url;
    public $data;
    public $client_ip;
    public $client_udid;
    public $created_time;
}
