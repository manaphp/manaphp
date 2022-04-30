<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:05
 */

namespace Tests\Mongodb\Models;

use ManaPHP\Data\Model\Attribute\Table;
use ManaPHP\Data\Mongodb\Model;

#[Table('_student')]
class Student extends Model
{
    public $id;
    public $age;
    public $name;

    public function fieldTypes()
    {
        return [
            '_id'  => 'objectid',
            'id'   => 'integer',
            'age'  => 'integer',
            'name' => 'string'
        ];
    }
}