<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:05
 */

namespace Tests\Models;

use ManaPHP\Data\Db\Model;

class Student extends Model
{
    public $id;
    public $age;
    public $name;

    public function table($context = null): string
    {
        return '_student';
    }
}