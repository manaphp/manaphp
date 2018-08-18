<?php
namespace App\Models;

class AdminMenu extends ModelBase
{
    /**
     * @var int
     */
    public $menu_id;

    /**
     * @var string
     */
    public $menu_name;

    /**
     * @var int
     */
    public $parent_id;

    /**
     * @var string
     */
    public $url;

    /**
     * @var int
     */
    public $display_order;

    /**
     * @var int
     */
    public $creator_id;

    /**
     * @var int
     */
    public $enabled;

    /**
     * @var int
     */
    public $created_time;

    public function getSource($context = null)
    {
        return 'admin_menu';
    }
}