<?php

namespace App\Areas\System\Models;

use App\Models\Model;

class DotenvLog extends Model
{
    public $id;
    public $app_id;
    public $env;
    public $created_date;
    public $created_time;

    public function table()
    {
        return 'dotenv_log';
    }

    public function primaryKey()
    {
        return 'id';
    }
}
