<?php
declare(strict_types=1);

namespace App\Areas\System\Models;

use App\Models\Model;
use ManaPHP\Model\Attribute\PrimaryKey;

#[PrimaryKey('id')]
class DotenvLog extends Model
{
    public int $id;
    public string $app_id;
    public string $env;
    public int $created_date;
    public int $created_time;
}
