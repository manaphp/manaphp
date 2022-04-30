<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:05
 */

namespace Tests\Models;

use ManaPHP\Data\Db\Model;
use ManaPHP\Data\Model\Attribute\Table;

#[Table('_student')]
class Student extends Model
{
    public $id;
    public $age;
    public $name;
}