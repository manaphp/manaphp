<?php

namespace App\Areas\System\Models;

use App\Models\Model;

/**
 * Class App\Areas\System\Models\DotenvLog
 */
class DotenvLog extends Model
{
    public $id;
    public $app_id;
    public $env;
    public $created_date;
    public $created_time;

    /**
     * @return string
     */
    public function table()
    {
        return 'dotenv_log';
    }

    /**
     * @return string
     */
    public function primaryKey()
    {
        return 'id';
    }
}
