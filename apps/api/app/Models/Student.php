<?php

namespace App\Models;

/**
 * Class Student
 */
class Student extends \ManaPHP\Db\Model
{
    public $id;
    public $age;
    public $name;

    public function getTable()
    {
        return '_student';
    }
}