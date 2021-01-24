<?php

namespace App\Models;

class Student extends Model
{
    public $id;
    public $age;
    public $name;

    public function table()
    {
        return '_student';
    }
}