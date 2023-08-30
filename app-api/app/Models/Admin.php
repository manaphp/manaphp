<?php
declare(strict_types=1);

namespace App\Models;

class Admin extends Model
{
    public int $admin_id;
    public int $admin_name;
    public int $status;
    public int $type;
    public int $tag;
    public string $email;
    public string $salt;
    public string $password;
    public string $white_ip;
    public $login_ip;
    public $login_time;
    public $session_id;
    public $creator_name;
    public $updator_name;
    public $created_time;
    public $updated_time;
}