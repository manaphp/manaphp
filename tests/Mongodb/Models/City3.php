<?php
namespace Tests\Mongodb\Models;

use ManaPHP\Mongodb\Model;

class City3 extends Model
{
    public static function getSource($context = null)
    {
        return 'the_city';
    }
}
