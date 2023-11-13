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
    #[Unique]
    public string $group_name;
    #[Length(0, 64)]
    public string $icon;
    #[Range(0, 127)]
    public int $display_order;
    public string $creator_name;
    public string $updator_name;
    public int $created_time;
    public int $updated_time;
}