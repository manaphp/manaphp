<?php

namespace Tests\Mongodb\Models;

use ManaPHP\Data\Mongodb\Model;

class City2 extends Model
{
    public function table($context = null)
    {
        return 'city';
    }
}