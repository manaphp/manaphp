<?php

namespace Tests\Mongodb\Models;

use ManaPHP\Mongodb\Model;

class City extends Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;

    public function getFieldTypes()
    {
        return [
            '_id'         => 'integer',
            'city_id'     => 'integer',
            'city'        => 'string',
            'country_id'  => 'integer',
            'last_update' => 'string'
        ];
    }
}