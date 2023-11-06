<?php
declare(strict_types=1);

namespace App\Areas\Menu\Models;

use App\Models\Model;
use ManaPHP\Model\Attribute\Table;
use ManaPHP\Validating\Rule\Attribute\Length;
use ManaPHP\Validating\Rule\Attribute\Range;
use ManaPHP\Validating\Rule\Attribute\Unique;

#[Table('menu_group')]
class Group extends Model
{
    public int $group_id;
    public string $group_name;
    public string $icon;
    public int $display_order;
    public string $creator_name;
    public string $updator_name;
    public int $created_time;
    public int $updated_time;

    public function rules(): array
    {
        return [
            'group_name'    => [new Unique()],
            'display_order' => [new Range(0, 127)],
            'icon'          => [new Length(0, 64)]
        ];
    }
}