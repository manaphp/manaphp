<?php
namespace App\Models;

use ManaPHP\Db\Model;

class AdminActionLog extends Model
{
    public $id;
    public $user_id;
    public $user_name;
    public $ip;
    public $udid;
    public $path;
    public $method;
    public $url;
    public $data;
    public $created_time;
}
