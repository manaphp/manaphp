<?php
declare(strict_types=1);

namespace App\Models;

use ManaPHP\Data\Model\Attribute\Table;

#[Table('_student')]
class Student extends Model
{
    public $id;
    public $age;
    public $name;
}