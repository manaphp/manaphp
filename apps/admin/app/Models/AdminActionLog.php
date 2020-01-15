<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\AdminActionLog
 */
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

    /**
     * @return string
     */
    public function getTable()
    {
        return 'admin_action_log';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'id';
    }
}
