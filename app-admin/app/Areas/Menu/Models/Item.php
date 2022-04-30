<?php
declare(strict_types=1);

namespace App\Areas\Menu\Models;

use App\Models\Model;
use ManaPHP\Data\Model\Attribute\Table;

#[Table('menu_item')]
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

    public function rules(): array
    {
        return [
            'item_name'     => ['length' => '2-32', 'unique' => 'group_id'],
            'group_id'      => 'exists',
            'url'           => ['length' => '1-128', 'unique' => 'group_id'],
            'display_order' => ['range' => '0-127'],
            'icon'          => ['length' => '0-64']
        ];
    }
}