<?php
declare(strict_types=1);

namespace App\Areas\Menu\Models;

use App\Models\Model;
use ManaPHP\Model\Attribute\Table;
use ManaPHP\Validating\Rule\Attribute\Exists;
use ManaPHP\Validating\Rule\Attribute\Length;
use ManaPHP\Validating\Rule\Attribute\Range;
use ManaPHP\Validating\Rule\Attribute\Unique;

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
            'item_name'     => [new Length(2, 32), new Unique(['group_id'])],
            'group_id'      => new Exists(),
            'url'           => [new Length(1, 128), new Unique(['group_id'])],
            'display_order' => new Range(0, 127),
            'icon'          => new Length(0, 64),
        ];
    }
}