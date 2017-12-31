<?php
namespace App\Admin\Menu\Models;

use ManaPHP\Db\Model;

class Group extends Model
{
    /**
     * @var int
     */
    public $group_id;

    /**
     * @var string
     */
    public $group_name;

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
        return 'menu_group';
    }
}