<?php

namespace App\Models;

/**
 * Class Student
 */
class Student extends Model
{
    public $id;
    public $age;
    public $name;

    public function getTable()
    {
        return '_student';
    }
}