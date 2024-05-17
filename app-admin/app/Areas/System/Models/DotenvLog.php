<?php
declare(strict_types=1);

namespace App\Areas\System\Models;

use App\Entities\Entity;
use ManaPHP\Persistence\Attribute\Id;

class DotenvLog extends Entity
{
    #[Id]
    public int $id;

    public string $app_id;
    public string $env;
    public int $created_date;
    public int $created_time;
}
