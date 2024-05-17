<?php
declare(strict_types=1);

namespace App\Entities;

use ManaPHP\Persistence\Attribute\Id;

class Student extends Entity
{
    #[Id]
    public $id;

    public $age;
    public $name;
}