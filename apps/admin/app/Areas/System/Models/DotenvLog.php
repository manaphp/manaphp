<?php

namespace App\Areas\System\Models;

use ManaPHP\Data\Db\Model;

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
    public function getTable()
    {
        return 'dotenv_log';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'id';
    }
}
