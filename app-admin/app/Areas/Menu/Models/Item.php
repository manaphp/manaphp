<?php
declare(strict_types=1);

namespace App\Areas\Menu\Models;

use App\Models\Model;
use ManaPHP\Model\Attribute\Table;

#[Table('menu_item')]
class Item extends Model
{
    public int $item_id;
    public string $item_name;
    public int $group_id;
    public int $display_order;
    public string $url;
    public string $icon;
    public string $creator_name;
    public string $updator_name;
    public int $created_time;
    public int $updated_time;

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