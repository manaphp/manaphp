<?php
declare(strict_types=1);

namespace App\Areas\System\Models;

use App\Entities\Entity;
use ManaPHP\Persistence\Attribute\PrimaryKey;

#[PrimaryKey('id')]
class DotenvLog extends Entity
{
    public int $id;
    public string $app_id;
    public string $env;
    public int $created_date;
    public int $created_time;
}
