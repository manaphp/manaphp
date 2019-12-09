<?php
namespace App\Areas\Menu\Models;

use ManaPHP\Db\Model;

class Item extends Model
{
    public $item_id;
    public $item_name;
    public $group_id;
    public $display_order;
    public $url;
    public $icon;
    public $creator_name;
    public $updator_name;
    public $created_time;
    public $updated_time;

    public function getTable()
    {
        return 'menu_item';
    }

    public function rules()
    {
        return [
            'item_name' => ['length' => '2-32'],
            'group_id' => 'exists',
            'url' => ['length' => '1-128'],
            'display_order' => ['range' => '0-127'],
            'icon' => ['length' => '0-64']
        ];
    }
}