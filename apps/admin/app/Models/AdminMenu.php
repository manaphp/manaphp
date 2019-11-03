<?php
namespace App\Models;

use ManaPHP\Db\Model;

class AdminMenu extends Model
{
    public $menu_id;
    public $menu_name;
    public $parent_id;
    public $url;
    public $display_order;
    public $creator_id;
    public $enabled;
    public $created_time;

    public function getTable()
    {
        return 'admin_menu';
    }
}