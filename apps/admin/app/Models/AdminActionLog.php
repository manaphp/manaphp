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
    public $client_ip;
    public $client_udid;
    public $path;
    public $method;
    public $url;
    public $data;
    public $created_time;

    /**
     * @param mixed $context
     *
     * @return string
     */
    public function getSource($context = null)
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
