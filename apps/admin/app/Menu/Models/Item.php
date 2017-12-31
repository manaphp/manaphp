<?php
namespace App\Admin\Menu\Models;

use ManaPHP\Db\Model;

class Item extends Model
{
    /**
     * @var int
     */
    public $item_id;

    /**
     * @var string
     */
    public $item_name;

    /**
     * @var int
     */
    public $group_id;

    /**
     * @var int
     */
    public $permission_id;

    /**
     * @var int
     */
    public $display_order;

    /**
     * @var int
     */
    public $creator_id;

    /**
     * @var string
     */
    public $creator_name;

    /**
     * @var int
     */
    public $created_time;

    /**
     * @var int
     */
    public $updated_time;

    public static function getSource($context = null)
    {
        return 'menu_item';
    }
}