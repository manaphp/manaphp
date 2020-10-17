<?php

namespace Tests\Mongodb\Models;

use ManaPHP\Mongodb\Model;

class City2 extends Model
{
    public function getTable($context = null)
    {
        return 'city';
    }
}