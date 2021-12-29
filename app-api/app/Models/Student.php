<?php
declare(strict_types=1);

namespace App\Models;

class Student extends Model
{
    public $id;
    public $age;
    public $name;

    public function table(): string
    {
        return '_student';
    }
}