<?php
declare(strict_types=1);

namespace App\Areas\Menu\Models;

use App\Models\Model;
use ManaPHP\Data\Model\Attribute\Table;

#[Table('menu_group')]
class Group extends Model
{
    public $group_id;
    public $group_name;
    public $display_order;
    public $icon;
    public $creator_name;
    public $updator_name;
    public $created_time;
    public $updated_time;

    public function rules(): array
    {
        return [
            'group_name'    => 'unique',
            'display_order' => ['range' => '0-127'],
            'icon'          => ['length' => '0-64']
        ];
    }
}