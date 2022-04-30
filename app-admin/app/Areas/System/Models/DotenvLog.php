<?php
declare(strict_types=1);

namespace App\Areas\System\Models;

use App\Models\Model;
use ManaPHP\Data\Model\Attribute\Table;

#[Table('dotenv_log')]
class DotenvLog extends Model
{
    public $id;
    public $app_id;
    public $env;
    public $created_date;
    public $created_time;

    public function primaryKey(): string
    {
        return 'id';
    }
}
