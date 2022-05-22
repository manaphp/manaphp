<?php
declare(strict_types=1);

namespace App\Areas\System\Models;

use App\Models\Model;
use ManaPHP\Data\Model\Attribute\PrimaryKey;

#[PrimaryKey('id')]
class DotenvLog extends Model
{
    public $id;
    public $app_id;
    public $env;
    public $created_date;
    public $created_time;
}
