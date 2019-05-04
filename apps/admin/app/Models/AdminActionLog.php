<?php
namespace App\Models;

use ManaPHP\Db\Model;

class AdminActionLog extends Model
{
    public $id;
    public $admin_id;
    public $admin_name;
    public $client_ip;
    public $client_udid;
    public $path;
    public $method;
    public $url;
    public $data;
    public $created_time;
}
