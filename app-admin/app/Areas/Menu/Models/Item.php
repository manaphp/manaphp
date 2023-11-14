<?php
declare(strict_types=1);

namespace App\Areas\Menu\Models;

use App\Models\Model;
use ManaPHP\Model\Attribute\Table;
use ManaPHP\Validating\Constraint\Attribute\Exists;
use ManaPHP\Validating\Constraint\Attribute\Length;
use ManaPHP\Validating\Constraint\Attribute\MaxLength;
use ManaPHP\Validating\Constraint\Attribute\Range;
use ManaPHP\Validating\Constraint\Attribute\Unique;

#[Table('menu_item')]
class Item extends Model
{
    public int $item_id;
    #[Length(2, 32), Unique(['group_id'])]
    public string $item_name;
    #[Exists]
    public int $group_id;
    #[Range(0, 127)]
    public int $display_order;
    #[Length(1, 128), Unique(['group_id'])]
    public string $url;
    #[MaxLength(64)]
    public string $icon;
    public string $creator_name;
    public string $updator_name;
    public int $created_time;
    public int $updated_time;
}